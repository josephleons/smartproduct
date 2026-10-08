<?php

namespace Tests\Feature;

use App\Enums\DeviceType;
use App\Enums\EventType;
use App\Jobs\RecordProductEvent;
use App\Models\Product;
use App\Models\ProductEvent;
use App\Services\VisitorContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordProductEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_event_with_hashed_ip_and_dedupes_within_30_minutes(): void
    {
        $owner = $this->makeTenant();
        $product = Product::factory()->for($this->firstBranch($owner))->create();
        $hash = VisitorContext::hashIp('41.59.1.1');
        $ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile';

        (new RecordProductEvent($product->id, EventType::Scan, $hash, $ua, now()->subMinutes(70)->toDateTimeString()))->handle();
        (new RecordProductEvent($product->id, EventType::Scan, $hash, $ua, now()->subMinutes(20)->toDateTimeString()))->handle();
        (new RecordProductEvent($product->id, EventType::Scan, $hash, $ua, now()->toDateTimeString()))->handle();

        $events = ProductEvent::withoutGlobalScopes()->orderBy('created_at')->get();
        $this->assertCount(3, $events);
        $this->assertSame([true, true, false], $events->pluck('is_unique')->all());
        $this->assertSame($product->company_id, $events[0]->company_id);
        $this->assertSame($product->branch_id, $events[0]->branch_id);
        $this->assertSame(DeviceType::Mobile, $events[0]->device_type);
        $this->assertNotSame('41.59.1.1', $events[0]->ip_hash);
        $this->assertSame(3, $product->qrCode->fresh()->scans_count);
    }
}
