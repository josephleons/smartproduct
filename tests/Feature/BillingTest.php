<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Contracts\PaymentGateway;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\PlanFeature;
use App\Enums\SubscriptionStatus;
use App\Jobs\CheckSubscriptions;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use App\Services\Billing\BillingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private function onboard(int $price = 30000, int $trialDays = 14): Company
    {
        app(BillingSettings::class)->update(['trial_days' => $trialDays, 'grace_days' => 7, 'invoice_days_before' => 7]);

        return app(CreateCompany::class)->handle([
            'name' => 'Posta Gadgets',
            'branch_name' => 'Posta',
            'owner_name' => 'Rehema',
            'owner_email' => 'rehema@example.com',
            'owner_password' => 'secret-pass-123',
            'plan_id' => Plan::factory()->create(['monthly_price' => $price])->id,
        ]);
    }

    private function runJob(): void
    {
        app()->call([new CheckSubscriptions, 'handle']);
    }

    private function owner(Company $company): User
    {
        return User::where('company_id', $company->id)->where('role', 'owner')->firstOrFail();
    }

    public function test_full_monthly_lifecycle_trial_bill_grace_read_only_and_payment(): void
    {
        $this->travelTo('2026-10-01');
        $company = $this->onboard();
        $subscription = $company->subscription;
        $this->assertSame(SubscriptionStatus::Trial, $subscription->status);
        $this->assertSame('2026-10-14', $subscription->paid_until->toDateString());
        $this->assertSame(0, $subscription->invoices()->count());

        // 7 days before the trial ends, the first monthly bill is issued.
        $this->travelTo('2026-10-08');
        $this->runJob();
        $invoice = $subscription->invoices()->sole();
        $this->assertSame(InvoiceStatus::Pending, $invoice->status);
        $this->assertSame(30000, $invoice->amount);
        $this->assertSame('2026-10-15', $invoice->due_date->toDateString());
        $this->assertMatchesRegularExpression('/^INV-202610-\d{5}$/', $invoice->number);

        // Running again does not issue a second bill.
        $this->runJob();
        $this->assertSame(1, $subscription->invoices()->count());

        // Unpaid after expiry: past due (still full access), bill overdue.
        $this->travelTo('2026-10-16');
        $this->runJob();
        $this->assertSame(SubscriptionStatus::PastDue, $subscription->fresh()->status);
        $this->assertSame('2026-10-21', $subscription->fresh()->grace_ends_at->toDateString());
        $this->assertSame(InvoiceStatus::Overdue, $invoice->fresh()->status);

        // Grace over: read-only.
        $this->travelTo('2026-10-22');
        $this->runJob();
        $this->assertSame(SubscriptionStatus::ReadOnly, $subscription->fresh()->status);

        // Owner submits an M-Pesa reference; superadmin confirms; a full month starts today.
        $owner = $this->owner($company);
        $this->actingAs($owner)->post("/settings/billing/invoices/{$invoice->id}/payments", [
            'amount' => '30,000', 'method' => 'mpesa', 'reference' => 'QJK3X8Y2LM',
        ])->assertRedirect();
        $payment = Payment::withoutGlobalScopes()->sole();
        $this->assertSame(PaymentStatus::Pending, $payment->status);

        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)->post("/admin/payments/{$payment->id}/confirm")->assertRedirect();

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->status);
        $this->assertSame('2026-11-21', $subscription->fresh()->paid_until->toDateString());
        $this->assertNull($subscription->fresh()->grace_ends_at);
    }

    public function test_paying_on_time_extends_from_the_current_period_end(): void
    {
        $this->travelTo('2026-10-01');
        $company = $this->onboard(trialDays: 0); // billed immediately, due today
        $invoice = $company->subscription->invoices()->sole();
        $this->assertSame('2026-10-01', $invoice->due_date->toDateString());

        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)->post("/admin/invoices/{$invoice->id}/payments", [
            'amount' => 30000, 'method' => 'cash', 'reference' => 'RCPT-001',
        ])->assertRedirect();

        $subscription = $company->subscription->fresh();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertSame('2026-10-31', $subscription->paid_until->toDateString());

        // Next bill is issued a week before 31 Oct and, once paid, continues seamlessly.
        $this->travelTo('2026-10-25');
        $this->runJob();
        $next = $subscription->invoices()->where('status', 'pending')->sole();
        $this->actingAs($admin)->post("/admin/invoices/{$next->id}/payments", ['amount' => 30000, 'method' => 'mpesa', 'reference' => 'X1'])->assertRedirect();
        $this->assertSame('2026-11-30', $subscription->fresh()->paid_until->toDateString());
    }

    public function test_partial_payments_settle_the_bill_only_when_the_full_amount_is_confirmed(): void
    {
        $this->travelTo('2026-10-01');
        $company = $this->onboard(trialDays: 0);
        $invoice = $company->subscription->invoices()->sole();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post("/admin/invoices/{$invoice->id}/payments", ['amount' => 10000, 'method' => 'mpesa', 'reference' => 'A']);
        $this->assertTrue($invoice->fresh()->isOpen());
        $this->assertSame(20000, $invoice->fresh()->balance());

        $this->actingAs($admin)->post("/admin/invoices/{$invoice->id}/payments", ['amount' => 20000, 'method' => 'mpesa', 'reference' => 'B']);
        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
    }

    public function test_free_plans_are_settled_automatically(): void
    {
        $this->travelTo('2026-10-01');
        $company = $this->onboard(price: 0, trialDays: 0);

        $this->assertSame(InvoiceStatus::Paid, $company->subscription->invoices()->sole()->status);
        $this->assertSame(SubscriptionStatus::Active, $company->subscription->fresh()->status);
    }

    public function test_job_asks_the_gateway_about_pending_payments(): void
    {
        $this->travelTo('2026-10-01');
        $company = $this->onboard(trialDays: 0);
        $invoice = $company->subscription->invoices()->sole();
        $confirmed = Payment::factory()->create(['invoice_id' => $invoice->id, 'gateway' => 'fake']);

        $other = Invoice::factory()->create();
        $rejected = Payment::factory()->create(['invoice_id' => $other->id, 'gateway' => 'fake', 'reference' => 'BAD']);

        config(['billing.gateways.fake' => FakeGateway::class]);
        $this->runJob();

        $this->assertSame(PaymentStatus::Confirmed, $confirmed->fresh()->status);
        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertSame(SubscriptionStatus::Active, $company->subscription->fresh()->status);
        $this->assertSame(PaymentStatus::Failed, $rejected->fresh()->status);
    }

    public function test_manual_gateway_leaves_payments_for_the_superadmin(): void
    {
        $payment = Payment::factory()->create();
        $this->runJob();
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_job_is_scheduled_hourly(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('CheckSubscriptions')->assertSuccessful();
    }

    public function test_read_only_companies_can_browse_and_pay_but_not_write_or_print(): void
    {
        Queue::fake();
        $owner = $this->makeTenant();
        $product = Product::factory()->for($this->firstBranch($owner))->create();
        $owner->company->subscription->update(['status' => SubscriptionStatus::ReadOnly, 'paid_until' => now()->subMonth()]);
        $invoice = Invoice::factory()->create(['subscription_id' => $owner->company->subscription->id, 'company_id' => $owner->company_id]);

        $this->actingAs($owner)->get('/products')->assertOk()->assertSee(__('Pay now'));
        $this->actingAs($owner)->post('/products', [])->assertRedirect('/settings/billing');
        $this->actingAs($owner)->patch("/products/{$product->id}/quick", ['price' => 1, 'quantity' => 1, 'status' => 'sold'])->assertRedirect('/settings/billing');
        $this->actingAs($owner)->get("/labels/pdf?products[]={$product->id}&template=standard_70x45&layout=single")->assertRedirect('/settings/billing');

        $this->actingAs($owner)->get('/settings/billing')->assertOk()->assertSee($invoice->number);
        $this->actingAs($owner)->post("/settings/billing/invoices/{$invoice->id}/payments", ['amount' => 30000, 'method' => 'mpesa', 'reference' => 'ABC'])->assertRedirect();
        $this->assertSame(1, Payment::withoutGlobalScopes()->count());

        // Customers still see the product page.
        $this->get('/p/'.$product->qrCode->token)->assertOk();
    }

    public function test_plan_limits_block_new_products_branches_and_users(): void
    {
        $owner = $this->makeTenant();
        $owner->company->subscription->plan->update(['max_products' => 1, 'max_branches' => 1, 'max_users' => 1]);
        Product::factory()->for($this->firstBranch($owner))->create();

        $this->actingAs($owner)->get('/products/create')->assertRedirect('/products')->assertSessionHas('error');
        $this->actingAs($owner)->get('/branches/create')->assertRedirect('/branches');
        $this->actingAs($owner)->post('/branches', ['name' => 'Second'])->assertRedirect('/branches');
        $this->actingAs($owner)->post('/settings/users', [
            'name' => 'X', 'email' => 'x@example.com', 'role' => 'staff',
            'password' => 'secret-pass-123', 'password_confirmation' => 'secret-pass-123',
        ])->assertRedirect('/settings/users')->assertSessionHas('error');

        $this->assertSame(1, $owner->company->branches()->withoutGlobalScopes()->count());
        $this->assertSame(1, $owner->company->users()->count());

        $this->assertSame(1, Product::count());
    }

    public function test_features_are_gated_by_plan(): void
    {
        $owner = $this->makeTenant();
        $owner->company->subscription->plan->update(['features' => [PlanFeature::Analytics->value]]);

        $this->actingAs($owner)->get('/analytics')->assertOk();
        $this->actingAs($owner)->get('/settings/categories')->assertOk();
        $this->actingAs($owner)->get('/settings/categories/create')->assertRedirect('/settings/billing');

        $owner->company->subscription->plan->update(['features' => []]);
        $this->actingAs($owner)->get('/analytics')->assertRedirect('/settings/billing');
    }

    public function test_superadmin_configures_plans_and_billing_settings(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get('/admin/plans')->assertOk();
        $this->actingAs($admin)->post('/admin/plans', [
            'name' => 'Premium', 'monthly_price' => '75,000', 'max_products' => '', 'max_users' => '10', 'max_branches' => '3',
            'features' => ['analytics', 'sms_notifications'], 'is_active' => '1',
        ])->assertRedirect('/admin/plans');

        $plan = Plan::where('name', 'Premium')->sole();
        $this->assertSame(75000, $plan->monthly_price);
        $this->assertNull($plan->max_products);
        $this->assertSame(10, $plan->max_users);
        $this->assertTrue($plan->hasFeature(PlanFeature::SmsNotifications));

        $this->actingAs($admin)->get('/admin/billing/settings')->assertOk();
        $this->actingAs($admin)->put('/admin/billing/settings', [
            'trial_days' => 30, 'grace_days' => 3, 'invoice_days_before' => 5, 'gateway' => 'manual',
            'payment_instructions' => 'Lipa Namba 123456',
        ])->assertRedirect();
        $this->assertSame(30, app(BillingSettings::class)->get('trial_days'));

        $this->actingAs($admin)->get('/admin/invoices')->assertOk();
    }

    public function test_billing_is_owner_only_and_tenant_isolated(): void
    {
        $ownerA = $this->makeTenant();
        $ownerB = $this->makeTenant();
        $invoiceB = Invoice::factory()->create(['subscription_id' => $ownerB->company->subscription->id, 'company_id' => $ownerB->company_id]);

        $this->actingAs($ownerA)->get('/settings/billing')->assertOk()->assertDontSee($invoiceB->number);
        $this->actingAs($ownerA)->post("/settings/billing/invoices/{$invoiceB->id}/payments", ['amount' => 1, 'method' => 'mpesa', 'reference' => 'X'])->assertNotFound();

        $manager = User::factory()->manager()->create(['company_id' => $ownerA->company_id]);
        $this->actingAs($manager)->get('/settings/billing')->assertForbidden();

        foreach (['/admin/plans', '/admin/invoices', '/admin/billing/settings'] as $url) {
            $this->actingAs($ownerA)->get($url)->assertForbidden();
        }
    }
}

class FakeGateway implements PaymentGateway
{
    public function check(Payment $payment): PaymentStatus
    {
        return $payment->reference === 'BAD' ? PaymentStatus::Failed : PaymentStatus::Confirmed;
    }
}
