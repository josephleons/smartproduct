<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabelTest extends TestCase
{
    use RefreshDatabase;

    private function labelQuery(array $products, array $overrides = []): string
    {
        return http_build_query($overrides + [
            'products' => $products,
            'template' => 'standard_70x45',
            'layout' => 'sheet',
            'fields' => ['company', 'specs', 'price', 'branch'],
            'copies' => 8,
        ]);
    }

    public function test_labels_page_preselects_products(): void
    {
        $user = $this->makeTenant('staff');
        $product = Product::factory()->for($this->firstBranch($user))->create();

        $this->actingAs($user)->get('/labels?products[]='.$product->id)->assertOk()->assertSee($product->sku);
    }

    public function test_preview_renders_the_sheet_with_qr_and_price(): void
    {
        $user = $this->makeTenant('staff');
        $product = Product::factory()->for($this->firstBranch($user))->create(['price' => 850000]);

        $response = $this->actingAs($user)->get('/labels/preview?'.$this->labelQuery([$product->id]))->assertOk();

        $response->assertSee('TZS 850,000')->assertSee(__('Scan for specs'))->assertSee('data:image/svg+xml;base64,', false);
        $this->assertSame(8, substr_count($response->getContent(), 'class="label"'));
        $this->assertSame(1, substr_count($response->getContent(), 'class="page"'));
    }

    public function test_qr_encodes_only_the_public_url(): void
    {
        $user = $this->makeTenant();
        $product = Product::factory()->for($this->firstBranch($user))->create();

        $this->assertSame(config('app.public_url').'/p/'.$product->qrCode->token, $product->qrCode->url());
    }

    public function test_pdf_downloads(): void
    {
        $user = $this->makeTenant();
        $product = Product::factory()->for($this->firstBranch($user))->create();

        $this->actingAs($user)->get('/labels/pdf?'.$this->labelQuery([$product->id], ['layout' => 'single']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_company_a_cannot_print_company_b_labels(): void
    {
        $userA = $this->makeTenant();
        $userB = $this->makeTenant();
        $productB = Product::factory()->for($this->firstBranch($userB))->create();

        $this->actingAs($userA)->get('/labels/preview?'.$this->labelQuery([$productB->id]))->assertNotFound();
        $this->actingAs($userA)->get('/labels/pdf?'.$this->labelQuery([$productB->id]))->assertNotFound();
        $this->actingAs($userA)->get('/labels')->assertDontSee($productB->sku);
    }

    public function test_labels_page_only_renders_preselected_products(): void
    {
        $user = $this->makeTenant('staff');
        [$picked, $other] = Product::factory()->count(2)->for($this->firstBranch($user))->create()->all();

        $this->actingAs($user)->get('/labels?products[]='.$picked->id)
            ->assertOk()
            ->assertSee($picked->sku)
            ->assertDontSee($other->sku);
    }

    public function test_product_picker_searches_and_paginates(): void
    {
        $user = $this->makeTenant('staff');
        $branch = $this->firstBranch($user);
        Product::factory()->count(25)->for($branch)->create(['brand' => 'HP', 'model' => 'EliteBook 840']);
        $dell = Product::factory()->for($branch)->create(['brand' => 'Dell', 'model' => 'Latitude 5420', 'serial_number' => 'SN-FIND-ME']);

        $this->actingAs($user)->getJson('/labels/products?q=EliteBook')
            ->assertOk()
            ->assertJsonCount(20, 'results')
            ->assertJsonPath('pagination.more', true);

        $this->actingAs($user)->getJson('/labels/products?q=EliteBook&page=2')
            ->assertJsonCount(5, 'results')
            ->assertJsonPath('pagination.more', false);

        $this->actingAs($user)->getJson('/labels/products?q=SN-FIND-ME')
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $dell->id)
            ->assertJsonPath('results.0.text', 'Dell Latitude 5420 ('.$dell->sku.')');
    }

    public function test_product_picker_respects_tenant_and_branch_boundaries(): void
    {
        $userA = $this->makeTenant();
        $userB = $this->makeTenant();
        Product::factory()->for($this->firstBranch($userB))->create(['model' => 'Secret 999']);

        $this->actingAs($userA)->getJson('/labels/products?q=Secret')->assertJsonCount(0, 'results');
        $this->actingAs($userA)->get('/labels/products')->assertDontSee('Secret 999');

        $staff = $this->makeTenant('staff', branchRestricted: true);
        $otherBranch = Branch::factory()->create(['company_id' => $staff->company_id]);
        Product::factory()->for($otherBranch)->create(['model' => 'Other Branch 1']);

        $this->actingAs($staff)->getJson('/labels/products?q=Other')->assertJsonCount(0, 'results');
    }
}
