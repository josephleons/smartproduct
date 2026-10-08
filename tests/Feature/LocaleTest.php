<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use App\Models\Product;
use App\Models\ProductSpec;
use App\Services\PublicProductCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The SW / EN switcher. The language lives in a cookie, not on tenant data,
 * so there is no cross-company record to protect here.
 */
class LocaleTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        return Product::factory()->for($this->firstBranch($this->makeTenant()))->create(['price' => 850000]);
    }

    public function test_switching_language_sets_a_cookie_and_goes_back(): void
    {
        $this->from('/login')
            ->get(route('locale.switch', 'sw'))
            ->assertRedirect('/login')
            ->assertCookie(SetLocale::COOKIE, 'sw');
    }

    public function test_unknown_languages_are_rejected(): void
    {
        $this->get('/language/fr')->assertNotFound()->assertCookieMissing(SetLocale::COOKIE);
    }

    public function test_login_page_follows_the_chosen_language(): void
    {
        $this->withCookie(SetLocale::COOKIE, 'sw')->get('/login')
            ->assertOk()->assertSee('lang="sw"', false)->assertSee('Karibu tena');

        $this->withCookie(SetLocale::COOKIE, 'en')->get('/login')
            ->assertOk()->assertSee('Welcome back');
    }

    public function test_dashboard_follows_the_chosen_language(): void
    {
        $this->actingAs($this->makeTenant())
            ->withCookie(SetLocale::COOKIE, 'sw')
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Ongeza bidhaa')
            ->assertSee(route('locale.switch', 'en'));
    }

    public function test_public_page_is_translated_and_cached_per_language(): void
    {
        Queue::fake();
        $product = $this->product();
        $token = $product->qrCode->token;
        ProductSpec::factory()->for($product)->create(['key' => 'smart_tv', 'label' => 'Smart TV', 'value' => 'Yes', 'unit' => null]);

        $this->withCookie(SetLocale::COOKIE, 'sw')->get("/p/{$token}")
            ->assertOk()->assertSee('Piga simu dukani')->assertSee('Ndiyo')->assertHeader('Vary', 'Cookie');
        $this->withCookie(SetLocale::COOKIE, 'en')->get("/p/{$token}")
            ->assertOk()->assertSee('Call the shop')->assertDontSee('Ndiyo');

        $this->assertTrue(Cache::has(PublicProductCache::key($token, 'sw')));
        $this->assertTrue(Cache::has(PublicProductCache::key($token, 'en')));

        $product->update(['price' => 700000]);

        $this->assertFalse(Cache::has(PublicProductCache::key($token, 'sw')));
        $this->assertFalse(Cache::has(PublicProductCache::key($token, 'en')));
    }

    public function test_swahili_covers_every_english_string(): void
    {
        $en = json_decode(file_get_contents(lang_path('en.json')), true);
        $sw = json_decode(file_get_contents(lang_path('sw.json')), true);

        $this->assertSame([], array_keys(array_diff_key($en, $sw)), 'Missing in lang/sw.json');
        $this->assertSame([], array_keys(array_diff_key($sw, $en)), 'Missing in lang/en.json');
    }
}
