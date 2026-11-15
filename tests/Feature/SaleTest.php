<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\SaleMethod;
use App\Enums\SaleStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    private function product(User $owner, array $attributes = []): Product
    {
        return Product::factory()->for($this->firstBranch($owner))->create($attributes + ['price' => 850000, 'quantity' => 1, 'status' => ProductStatus::Available]);
    }

    private function token(Product $product): string
    {
        return $product->qrCode->token;
    }

    private function cash(array $overrides = []): array
    {
        return $overrides + ['method' => 'cash', 'amount' => '800,000', 'customer_name' => 'Rehema Juma', 'customer_phone' => '0712 345 678'];
    }

    private function sale(Product $product, array $attributes = []): Sale
    {
        return Sale::factory()->create($attributes + ['product_id' => $product->id]);
    }

    // ---- Customer side (public QR pages) ----

    public function test_product_page_offers_pay_only_while_the_price_is_shown(): void
    {
        Queue::fake();
        $owner = $this->makeTenant();
        $available = $this->product($owner);
        $sold = $this->product($owner, ['status' => ProductStatus::Sold]);

        $this->get('/p/'.$this->token($available))->assertSee(route('public.sale.create', $this->token($available)));
        $this->get('/p/'.$this->token($sold))->assertDontSee('/pay');

        $this->get('/p/'.$this->token($available).'/pay')->assertOk()->assertSee('TZS 850,000')->assertSee(__('Cash'));
        $this->get('/p/'.$this->token($sold).'/pay')->assertRedirect('/p/'.$this->token($sold));
        $this->get('/p/ZZZZZZZ/pay')->assertNotFound();
    }

    public function test_pay_page_shows_the_branch_lipa_namba_over_the_company_one(): void
    {
        $owner = $this->makeTenant();
        $owner->company->update(['payment_numbers' => ['mpesa' => '111111', 'airtel_money' => '222222']]);
        $this->firstBranch($owner)->update(['payment_numbers' => ['mpesa' => '999999']]);
        $product = $this->product($owner);

        $this->get('/p/'.$this->token($product).'/pay')
            ->assertSee('M-Pesa')->assertSee('999999')->assertDontSee('111111')
            ->assertSee('Airtel Money')->assertSee('222222')
            ->assertDontSee('HaloPesa');
    }

    public function test_customer_confirms_cash_with_a_discounted_amount(): void
    {
        $owner = $this->makeTenant();
        $product = $this->product($owner);

        $response = $this->post('/p/'.$this->token($product).'/pay', $this->cash());

        $sale = Sale::withoutGlobalScopes()->sole();
        $response->assertRedirect('/p/'.$this->token($product).'/pay/'.$sale->reference);
        $this->assertSame(SaleStatus::Pending, $sale->status);
        $this->assertSame(SaleMethod::Cash, $sale->method);
        $this->assertSame(800000, $sale->amount);
        $this->assertSame(850000, $sale->listed_price);
        $this->assertSame('255712345678', $sale->customer_phone);
        $this->assertSame($owner->company_id, $sale->company_id);
        $this->assertMatchesRegularExpression('/^[2-9A-HJ-NP-Z]{6}$/', $sale->reference);

        // The status page shows the reference but no personal data.
        $this->get('/p/'.$this->token($product).'/pay/'.$sale->reference)
            ->assertOk()->assertSee($sale->reference)->assertSee('http-equiv="refresh"', false)
            ->assertDontSee('Rehema')->assertDontSee('712345678');
        $this->get('/p/'.$this->token($product).'/pay/XXXXXX')->assertNotFound();

        $this->assertSame(ProductStatus::Available, $product->fresh()->status, 'nothing changes until staff approve');
    }

    public function test_mobile_payment_needs_a_configured_network_and_transaction_code(): void
    {
        $owner = $this->makeTenant();
        $owner->company->update(['payment_numbers' => ['mpesa' => '123456']]);
        $product = $this->product($owner);
        $url = '/p/'.$this->token($product).'/pay';

        $this->post($url, $this->cash(['method' => 'mobile']))->assertSessionHasErrors(['network', 'transaction_code']);
        $this->post($url, $this->cash(['method' => 'mobile', 'network' => 'halopesa', 'transaction_code' => 'QJK3X8Y2LM']))
            ->assertSessionHasErrors('network');
        $this->post($url, $this->cash(['method' => 'mobile', 'network' => 'mpesa', 'transaction_code' => 'qjk3x8y2lm']))
            ->assertSessionHasNoErrors();

        $sale = Sale::withoutGlobalScopes()->sole();
        $this->assertSame('mpesa', $sale->network);
        $this->assertSame('123456', $sale->payment_number);
        $this->assertSame('QJK3X8Y2LM', $sale->transaction_code);
    }

    public function test_customer_input_is_validated_and_rate_limited(): void
    {
        $owner = $this->makeTenant();
        $product = $this->product($owner);
        $url = '/p/'.$this->token($product).'/pay';

        $this->post($url, $this->cash(['amount' => '0', 'customer_name' => '', 'customer_phone' => '12345']))
            ->assertSessionHasErrors(['amount', 'customer_name', 'customer_phone']);

        config(['payments.max_requests_per_hour' => 2]);
        $this->post($url, $this->cash())->assertRedirect();
        $this->post($url, $this->cash())->assertStatus(429);
    }

    public function test_sold_products_cannot_be_paid_for(): void
    {
        $owner = $this->makeTenant();
        $product = $this->product($owner, ['status' => ProductStatus::Inactive]);

        $this->post('/p/'.$this->token($product).'/pay', $this->cash())->assertRedirect('/p/'.$this->token($product));
        $this->assertSame(0, Sale::withoutGlobalScopes()->count());
    }

    // ---- Staff side (Payments / PWA) ----

    public function test_staff_scan_a_sticker_and_see_its_waiting_payments(): void
    {
        $staff = $this->makeTenant('staff');
        $product = $this->product($staff);
        $waiting = $this->sale($product, ['customer_name' => 'Rehema Juma', 'transaction_code' => 'QJK3X8Y2LM']);
        $this->sale($product, ['status' => SaleStatus::Approved, 'customer_name' => 'Old Buyer']);

        $this->actingAs($staff)->get('/sales')->assertOk()->assertSee('Rehema Juma')->assertDontSee('Old Buyer');
        $this->actingAs($staff)->get('/sales?status=approved')->assertSee('Old Buyer')->assertDontSee('Rehema Juma');

        // Scanner opens /sales/product/{token}; typing the SKU works too (lowercase is fine).
        $this->actingAs($staff)->get('/sales/product/'.$this->token($product))
            ->assertOk()->assertSee($product->model)->assertSee('QJK3X8Y2LM')->assertSee(route('sales.approve', $waiting));
        $this->actingAs($staff)->get('/sales/lookup?code='.strtolower($product->sku))->assertRedirect('/sales/product/'.strtolower($product->sku));
        $this->actingAs($staff)->get('/sales/product/'.strtolower($product->sku))->assertOk()->assertSee($product->model);
        $this->actingAs($staff)->get('/sales/product/NOPE123')->assertRedirect('/sales')->assertSessionHas('error');
        $this->actingAs($staff)->get('/sales/'.$waiting->id)->assertOk()->assertSee($waiting->reference);
    }

    public function test_approving_takes_one_unit_and_marks_sold_at_zero(): void
    {
        Queue::fake();
        $staff = $this->makeTenant('staff');
        $product = $this->product($staff, ['quantity' => 2]);
        [$first, $second] = [$this->sale($product), $this->sale($product)];

        $this->actingAs($staff)->post("/sales/{$first->id}/approve", ['received_amount' => '800,000'])->assertSessionHas('success');
        $product->refresh();
        $this->assertSame(1, $product->quantity);
        $this->assertSame(ProductStatus::Available, $product->status);

        $first->refresh();
        $this->assertSame(SaleStatus::Approved, $first->status);
        $this->assertSame(800000, $first->received_amount);
        $this->assertSame($staff->id, $first->reviewed_by);

        $this->get('/p/'.$this->token($product))->assertSee('TZS 850,000');

        $this->actingAs($staff)->post("/sales/{$second->id}/approve", ['received_amount' => 850000]);
        $product->refresh();
        $this->assertSame(0, $product->quantity);
        $this->assertSame(ProductStatus::Sold, $product->status);

        // The cached QR page updates at once: Sold, no price, no Pay button.
        $this->get('/p/'.$this->token($product))->assertDontSee('TZS 850,000')->assertDontSee('/pay');
        // And the customer's status page says approved.
        $this->get('/p/'.$this->token($product).'/pay/'.$second->reference)->assertSee(__('Payment approved. Thank you for your purchase!'));
    }

    public function test_a_payment_cannot_be_approved_twice_or_after_the_product_is_sold(): void
    {
        $staff = $this->makeTenant('staff');
        $product = $this->product($staff);
        [$first, $second] = [$this->sale($product), $this->sale($product)];

        $this->actingAs($staff)->post("/sales/{$first->id}/approve", ['received_amount' => 1]);
        $this->actingAs($staff)->post("/sales/{$first->id}/approve", ['received_amount' => 1])->assertSessionHasErrors('sale');
        $this->actingAs($staff)->post("/sales/{$second->id}/approve", ['received_amount' => 1])->assertSessionHasErrors('sale');

        $this->assertSame(SaleStatus::Pending, $second->fresh()->status);
        $this->actingAs($staff)->post("/sales/{$second->id}/reject", ['note' => 'Already sold'])->assertSessionHas('success');
        $this->assertSame(SaleStatus::Rejected, $second->fresh()->status);
        $this->assertSame(0, $product->fresh()->quantity);
    }

    public function test_unreviewed_payments_expire(): void
    {
        $staff = $this->makeTenant('staff');
        $product = $this->product($staff);
        $old = $this->sale($product, ['created_at' => now()->subHours(25)]);
        $fresh = $this->sale($product);

        // Past the deadline it can no longer be approved, even before the hourly sweep.
        $this->actingAs($staff)->post("/sales/{$old->id}/approve", ['received_amount' => 1])->assertSessionHasErrors('sale');
        $this->actingAs($staff)->get('/sales')->assertDontSee($old->reference)->assertSee($fresh->reference);
        $this->actingAs($staff)->get('/sales?status=expired')->assertSee($old->reference);

        $this->artisan('sales:expire')->assertSuccessful();
        $this->assertSame(SaleStatus::Expired, $old->fresh()->status);
        $this->assertSame(SaleStatus::Pending, $fresh->fresh()->status);
    }

    public function test_company_a_cannot_see_or_review_company_b_payments(): void
    {
        $userA = $this->makeTenant();
        $userB = $this->makeTenant();
        $productB = $this->product($userB);
        $saleB = $this->sale($productB, ['customer_name' => 'Secret Buyer']);

        $this->actingAs($userA)->get('/sales')->assertDontSee('Secret Buyer');
        $this->actingAs($userA)->get('/sales/'.$saleB->id)->assertNotFound();
        $this->actingAs($userA)->get('/sales/product/'.$this->token($productB))->assertRedirect('/sales');
        $this->actingAs($userA)->post("/sales/{$saleB->id}/approve", ['received_amount' => 1])->assertNotFound();
        $this->actingAs($userA)->post("/sales/{$saleB->id}/reject")->assertNotFound();

        $this->assertSame(SaleStatus::Pending, $saleB->fresh()->status);
    }

    public function test_branch_restricted_staff_only_review_their_branch(): void
    {
        $staff = $this->makeTenant('staff', branchRestricted: true);
        $otherBranch = Branch::factory()->create(['company_id' => $staff->company_id]);
        $theirs = Product::factory()->for($otherBranch)->create(['price' => 1000]);
        $sale = $this->sale($theirs, ['customer_name' => 'Other Branch Buyer']);

        $this->actingAs($staff)->get('/sales')->assertDontSee('Other Branch Buyer');
        $this->actingAs($staff)->get('/sales/product/'.$this->token($theirs))
            ->assertRedirect('/sales')
            ->assertSessionHas('error', fn ($message) => str_contains($message, $otherBranch->name) && str_contains($message, $this->firstBranch($staff)->name));
        $this->actingAs($staff)->get('/sales/product/'.$this->token(Product::factory()->for($this->firstBranch($staff))->create()))->assertOk();
        $this->actingAs($staff)->post("/sales/{$sale->id}/approve", ['received_amount' => 1])->assertForbidden();
    }

    public function test_read_only_subscription_blocks_approval_but_not_customers(): void
    {
        $staff = $this->makeTenant('staff');
        $staff->company->subscription->update(['status' => SubscriptionStatus::ReadOnly, 'paid_until' => now()->subMonths(2)]);
        $product = $this->product($staff);
        $sale = $this->sale($product);

        $this->actingAs($staff)->post("/sales/{$sale->id}/approve", ['received_amount' => 1])->assertRedirect(route('billing.show'));
        $this->assertSame(SaleStatus::Pending, $sale->fresh()->status);

        auth()->logout();
        $this->post('/p/'.$this->token($product).'/pay', $this->cash())->assertRedirect();
    }

    public function test_owner_saves_lipa_namba_in_settings_and_branches(): void
    {
        $owner = $this->makeTenant();
        $company = $owner->company;
        $branch = $this->firstBranch($owner);

        $this->actingAs($owner)->get('/settings/company')->assertOk()->assertSee('Mixx by Yas');
        $this->actingAs($owner)->put('/settings/company', [
            'name' => $company->name,
            'payment_numbers' => ['mpesa' => ' 123456 ', 'tigopesa' => '', 'bogus' => '1'],
        ])->assertSessionHasErrors('payment_numbers');

        $this->actingAs($owner)->put('/settings/company', [
            'name' => $company->name,
            'payment_numbers' => ['mpesa' => ' 123456 ', 'tigopesa' => ''],
        ])->assertSessionHasNoErrors();
        $this->assertSame(['mpesa' => '123456'], $company->fresh()->payment_numbers);

        $this->actingAs($owner)->put("/branches/{$branch->id}", [
            'name' => $branch->name, 'is_active' => 1, 'payment_numbers' => ['airtel_money' => '777777'],
        ])->assertSessionHasNoErrors();
        $this->assertSame(['airtel_money' => '777777'], $branch->fresh()->payment_numbers);
    }
}
