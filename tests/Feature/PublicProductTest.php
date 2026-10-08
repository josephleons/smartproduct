<?php

namespace Tests\Feature;

use App\Enums\EventType;
use App\Jobs\RecordProductEvent;
use App\Models\Product;
use App\Models\ProductEvent;
use App\Services\PublicProductCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PublicProductTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE_UA = 'Mozilla/5.0 (Linux; Android 12; TECNO) Mobile Safari/537.36';

    private function product(array $attributes = []): Product
    {
        $owner = $this->makeTenant();

        return Product::factory()->for($this->firstBranch($owner))->create($attributes + ['price' => 850000]);
    }

    public function test_scan_shows_the_product_and_queues_a_scan_event(): void
    {
        Queue::fake();
        $product = $this->product();

        $this->withHeader('User-Agent', self::PHONE_UA)
            ->get('/p/'.$product->qrCode->token)
            ->assertOk()
            ->assertSee($product->model)
            ->assertSee('TZS 850,000')
            ->assertDontSee('/build/assets/core'); // no Vuexy bundle on the public page

        Queue::assertPushed(RecordProductEvent::class, fn ($job) => $job->type === EventType::Scan
            && $job->productId === $product->id
            && strlen($job->ipHash) === 64);
        $this->assertSame(0, ProductEvent::withoutGlobalScopes()->count(), 'Nothing is written synchronously.');
    }

    public function test_tokens_are_case_insensitive(): void
    {
        Queue::fake();
        $product = $this->product();

        $this->get('/p/'.strtolower($product->qrCode->token))->assertOk();
    }

    public function test_sold_products_hide_the_price_but_keep_contacts(): void
    {
        Queue::fake();
        $product = $this->product(['status' => 'sold']);

        $this->get('/p/'.$product->qrCode->token)
            ->assertOk()
            ->assertSee(__('Sold'))
            ->assertDontSee('TZS 850,000')
            ->assertSee('/go/whatsapp');
    }

    public function test_unknown_or_deleted_tokens_show_a_friendly_404(): void
    {
        $this->get('/p/ZZZZZZZ')->assertNotFound()->assertSee(__('Product not found'));
        $this->get('/p/bad')->assertNotFound();

        $product = $this->product();
        $product->delete();
        $this->get('/p/'.$product->qrCode->token)->assertNotFound();
    }

    public function test_bots_are_not_tracked(): void
    {
        Queue::fake();
        $product = $this->product();

        $this->withHeader('User-Agent', 'WhatsApp/2.23.20.0')->get('/p/'.$product->qrCode->token)->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_contact_buttons_record_and_redirect(): void
    {
        Queue::fake();
        $product = $this->product();
        $product->branch->update(['whatsapp' => '0712 345 678', 'phone' => '0754000111']);
        $token = $product->qrCode->token;

        $this->withHeader('User-Agent', self::PHONE_UA)->get("/p/{$token}/go/whatsapp")
            ->assertRedirectContains('https://wa.me/255712345678?text=');
        $this->withHeader('User-Agent', self::PHONE_UA)->get("/p/{$token}/go/call")
            ->assertRedirect('tel:+255754000111');

        Queue::assertPushed(RecordProductEvent::class, fn ($job) => $job->type === EventType::WhatsappClick);
        Queue::assertPushed(RecordProductEvent::class, fn ($job) => $job->type === EventType::CallClick);
    }

    public function test_similar_products_come_from_the_same_company_and_category(): void
    {
        Queue::fake();
        $product = $this->product();
        $sibling = Product::factory()->for($product->branch)->create(['category_id' => $product->category_id, 'model' => 'Sibling 1']);
        $soldSibling = Product::factory()->for($product->branch)->create(['category_id' => $product->category_id, 'model' => 'Gone 2', 'status' => 'sold']);
        $otherCompany = $this->product(['category_id' => $product->category_id, 'model' => 'Stranger 3']);

        $this->withHeader('User-Agent', self::PHONE_UA)->get("/p/{$product->qrCode->token}/similar")
            ->assertOk()
            ->assertSee('Sibling 1')
            ->assertDontSee('Gone 2')
            ->assertDontSee('Stranger 3');

        Queue::assertPushed(RecordProductEvent::class, fn ($job) => $job->type === EventType::SimilarClick);
    }

    public function test_a_seller_from_another_company_can_still_view_the_page(): void
    {
        Queue::fake();
        $product = $this->product();

        $this->actingAs($this->makeTenant())->get('/p/'.$product->qrCode->token)->assertOk()->assertSee($product->model);
    }

    public function test_page_is_cached_and_busted_on_update(): void
    {
        Queue::fake();
        $product = $this->product();
        $token = $product->qrCode->token;

        $this->get("/p/{$token}")->assertSee('TZS 850,000');
        $this->assertTrue(Cache::has(PublicProductCache::key($token)));

        $product->update(['price' => 700000]);
        $this->assertFalse(Cache::has(PublicProductCache::key($token)));

        $this->get("/p/{$token}")->assertSee('TZS 700,000');
    }

    public function test_public_routes_are_rate_limited(): void
    {
        Queue::fake();
        $product = $this->product();

        for ($i = 0; $i < 60; $i++) {
            $this->get('/p/'.$product->qrCode->token);
        }

        $this->get('/p/'.$product->qrCode->token)->assertStatus(429);
    }
}
