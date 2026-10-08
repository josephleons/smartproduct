<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private function payload(User $user, array $overrides = []): array
    {
        return array_replace_recursive([
            'category_id' => $this->laptopCategory()->id,
            'branch_id' => $this->firstBranch($user)->id,
            'brand' => 'Dell',
            'model' => 'Latitude 5420',
            'condition' => 'grade_a',
            'price' => '850,000',
            'quantity' => 1,
            'warranty_months' => 3,
            'status' => 'available',
            'specs' => ['processor' => 'Core i5', 'ram' => 16, 'storage' => '512GB SSD', 'operating_system' => 'Windows 11'],
        ], $overrides);
    }

    public function test_product_pages_render(): void
    {
        $user = $this->makeTenant();
        $product = Product::factory()->for($this->firstBranch($user))->create();

        $this->actingAs($user)->get('/products')->assertOk()->assertSee($product->model);
        $this->actingAs($user)->get('/products/create')->assertOk();
        $this->actingAs($user)->get("/products/{$product->id}")->assertOk()->assertSee($product->qrCode->token);
        $this->actingAs($user)->get("/products/{$product->id}/edit")->assertOk();
    }

    public function test_creating_a_product_stores_specs_sku_and_a_permanent_token(): void
    {
        $user = $this->makeTenant();

        $response = $this->actingAs($user)->post('/products', $this->payload($user));

        $product = Product::firstOrFail();
        $response->assertRedirect("/products/{$product->id}");

        $this->assertSame(850000, $product->price);
        $this->assertSame($user->company_id, $product->company_id);
        $this->assertMatchesRegularExpression('/^TZ-LAP5420-\d{3}$/', $product->sku);
        $this->assertMatchesRegularExpression('/^[2-9A-HJ-NP-Z]{7}$/', $product->qrCode->token);
        $this->assertSame('16 GB', $product->specs->firstWhere('key', 'ram')->displayValue());
        $this->assertSame('Core i5 · 16 GB · 512GB SSD', $product->labelSpecLine());
    }

    public function test_required_spec_fields_are_validated(): void
    {
        $user = $this->makeTenant();

        $this->actingAs($user)
            ->post('/products', $this->payload($user, ['specs' => ['processor' => '']]))
            ->assertSessionHasErrors('specs.processor');

        $this->actingAs($user)
            ->post('/products', $this->payload($user, ['specs' => ['operating_system' => 'Windows 95']]))
            ->assertSessionHasErrors('specs.operating_system');
    }

    public function test_editing_never_changes_the_token(): void
    {
        $user = $this->makeTenant();
        $this->actingAs($user)->post('/products', $this->payload($user));
        $product = Product::firstOrFail();
        $token = $product->qrCode->token;

        $this->actingAs($user)
            ->put("/products/{$product->id}", $this->payload($user, ['price' => 799000, 'model' => 'Latitude 5430']))
            ->assertRedirect();

        $product->refresh();
        $this->assertSame(799000, $product->price);
        $this->assertSame('Latitude 5430', $product->model);
        $this->assertSame($token, $product->qrCode->token);
    }

    public function test_quick_update_changes_price_and_status(): void
    {
        $user = $this->makeTenant('staff');
        $product = Product::factory()->for($this->firstBranch($user))->create();

        $this->actingAs($user)
            ->patch("/products/{$product->id}/quick", ['price' => '1,200,000', 'quantity' => 0, 'status' => 'sold'])
            ->assertRedirect();

        $this->assertSame(1200000, $product->fresh()->price);
        $this->assertSame(ProductStatus::Sold, $product->fresh()->status);
    }

    public function test_only_owners_and_managers_can_delete(): void
    {
        $staff = $this->makeTenant('staff');
        $product = Product::factory()->for($this->firstBranch($staff))->create();
        $this->actingAs($staff)->delete("/products/{$product->id}")->assertForbidden();

        $manager = User::factory()->manager()->create(['company_id' => $staff->company_id]);
        $this->actingAs($manager)->delete("/products/{$product->id}")->assertRedirect('/products');
        $this->assertSoftDeleted($product);
    }

    public function test_branch_restricted_staff_only_see_their_branch(): void
    {
        $staff = $this->makeTenant('staff', branchRestricted: true);
        $ownBranch = $this->firstBranch($staff);
        $otherBranch = Branch::factory()->create(['company_id' => $staff->company_id]);

        $mine = Product::factory()->for($ownBranch)->create(['model' => 'Mine 100']);
        $theirs = Product::factory()->for($otherBranch)->create(['model' => 'Theirs 200']);

        $this->actingAs($staff)->get('/products')->assertSee('Mine 100')->assertDontSee('Theirs 200');
        $this->actingAs($staff)->get("/products/{$theirs->id}")->assertForbidden();
        $this->actingAs($staff)
            ->post('/products', $this->payload($staff, ['branch_id' => $otherBranch->id]))
            ->assertSessionHasErrors('branch_id');
    }

    public function test_company_a_cannot_see_or_modify_company_b_products(): void
    {
        $userA = $this->makeTenant();
        $userB = $this->makeTenant();
        $productB = Product::factory()->for($this->firstBranch($userB))->create(['model' => 'Secret 999']);

        $this->actingAs($userA)->get('/products')->assertDontSee('Secret 999');
        $this->actingAs($userA)->get("/products/{$productB->id}")->assertNotFound();
        $this->actingAs($userA)->get("/products/{$productB->id}/edit")->assertNotFound();
        $this->actingAs($userA)->put("/products/{$productB->id}", $this->payload($userA))->assertNotFound();
        $this->actingAs($userA)->patch("/products/{$productB->id}/quick", ['price' => 1, 'quantity' => 1, 'status' => 'sold'])->assertNotFound();
        $this->actingAs($userA)->delete("/products/{$productB->id}")->assertNotFound();

        // Branch from another company cannot be used either.
        $this->actingAs($userA)
            ->post('/products', $this->payload($userA, ['branch_id' => $this->firstBranch($userB)->id]))
            ->assertSessionHasErrors('branch_id');

        $this->assertSame('Secret 999', $productB->fresh()->model);
    }
}
