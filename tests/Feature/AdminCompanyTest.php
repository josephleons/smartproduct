<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCompanyTest extends TestCase
{
    use RefreshDatabase;

    private function companyPayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Posta Gadgets',
            'plan_id' => Plan::factory()->create()->id,
            'phone' => '0713000000',
            'branch_name' => 'Posta',
            'branch_location' => 'Posta, Dar es Salaam',
            'owner_name' => 'Rehema Ali',
            'owner_email' => 'rehema@example.com',
            'owner_password' => 'secret-pass-123',
            'owner_password_confirmation' => 'secret-pass-123',
        ];
    }

    public function test_superadmin_creates_a_company_with_branch_and_owner(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get('/admin/companies/create')->assertOk();
        $this->actingAs($admin)->post('/admin/companies', $this->companyPayload())->assertRedirect('/admin/companies');

        $company = Company::where('name', 'Posta Gadgets')->firstOrFail();
        $owner = User::where('email', 'rehema@example.com')->firstOrFail();

        $this->assertTrue($company->is_active);
        $this->assertSame($company->id, $owner->company_id);
        $this->assertSame(UserRole::Owner, $owner->role);
        $this->assertSame(['Posta'], Branch::withoutGlobalScopes()->where('company_id', $company->id)->pluck('name')->all());

        // The new owner can log in and use their dashboard.
        $this->post('/logout');
        $this->post('/login', ['email' => 'rehema@example.com', 'password' => 'secret-pass-123'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk();
    }

    public function test_superadmin_lists_companies_across_tenants(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $ownerA = $this->makeTenant();
        $ownerB = $this->makeTenant();
        Product::factory()->count(2)->for($this->firstBranch($ownerB))->create();

        $response = $this->actingAs($admin)->get('/admin/companies')->assertOk()
            ->assertSee($ownerA->company->name)
            ->assertSee($ownerB->company->name);

        $companyB = $response->viewData('companies')->firstWhere('id', $ownerB->company_id);
        $this->assertSame(2, $companyB->products_count);
        $this->assertSame(1, $companyB->branches_count);
    }

    public function test_superadmin_is_sent_to_admin_instead_of_a_tenant_dashboard(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin/companies');
        $this->actingAs($admin)->get('/products')->assertRedirect('/admin/companies');
    }

    public function test_company_users_cannot_reach_the_admin_area(): void
    {
        foreach (['owner', 'manager', 'staff'] as $role) {
            $user = $this->makeTenant($role);
            $this->actingAs($user)->get('/admin/companies')->assertForbidden();
            $this->actingAs($user)->post('/admin/companies', $this->companyPayload(['owner_email' => "$role@example.com"]))->assertForbidden();
        }

        $this->assertSame(0, Company::where('name', 'Posta Gadgets')->count());
    }

    public function test_owners_cannot_promote_users_to_superadmin(): void
    {
        $owner = $this->makeTenant();

        $this->actingAs($owner)->post('/settings/users', [
            'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'role' => 'superadmin',
            'password' => 'secret-pass-123', 'password_confirmation' => 'secret-pass-123',
        ])->assertSessionHasErrors('role');
    }

    public function test_deactivated_company_users_are_logged_out_but_labels_keep_working(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = $this->makeTenant();
        $product = Product::factory()->for($this->firstBranch($owner))->create();

        $this->actingAs($admin)->put("/admin/companies/{$owner->company_id}", [
            'name' => $owner->company->name,
            'is_active' => '0',
            'plan_id' => $owner->company->subscription->plan_id,
        ])->assertRedirect('/admin/companies');
        $this->assertFalse($owner->company->fresh()->is_active);

        $this->actingAs($owner->fresh())->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();

        $this->get('/p/'.$product->qrCode->token)->assertOk();
    }
}
