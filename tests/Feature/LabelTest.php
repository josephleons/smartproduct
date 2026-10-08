<?php

namespace Tests\Feature;

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
}
