<?php

namespace Tests\Feature;

use App\Enums\EventType;
use App\Models\Product;
use App\Models\ProductEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_scan_metrics_for_their_company_only(): void
    {
        $ownerA = $this->makeTenant();
        $ownerB = $this->makeTenant();
        $productA = Product::factory()->for($this->firstBranch($ownerA))->create();
        $productB = Product::factory()->for($this->firstBranch($ownerB))->create();

        ProductEvent::factory()->count(4)->for($productA)->create();
        ProductEvent::factory()->for($productA)->create(['type' => EventType::Scan, 'is_unique' => false]);
        ProductEvent::factory()->for($productA)->create(['type' => EventType::WhatsappClick]);
        ProductEvent::factory()->count(9)->for($productB)->create();

        $response = $this->actingAs($ownerA)->get('/analytics?days=7')->assertOk();

        $summary = $response->viewData('summary');
        $this->assertSame(5, $summary['scans']);
        $this->assertSame(4, $summary['unique_scans']);
        $this->assertSame(1, $summary['products_scanned']);
        $this->assertSame(1, $summary['whatsapp_clicks']);
        $this->assertSame(25.0, $summary['contact_rate']);
    }

    public function test_dashboard_renders_with_stats(): void
    {
        $owner = $this->makeTenant();
        Product::factory()->count(2)->for($this->firstBranch($owner))->create();
        Product::factory()->for($this->firstBranch($this->makeTenant()))->create();

        $response = $this->actingAs($owner)->get('/dashboard')->assertOk();
        $this->assertSame(2, $response->viewData('stats')['products']);
    }

    public function test_staff_cannot_view_analytics(): void
    {
        $this->actingAs($this->makeTenant('staff'))->get('/analytics')->assertForbidden();
    }
}
