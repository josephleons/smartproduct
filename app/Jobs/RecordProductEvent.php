<?php

namespace App\Jobs;

use App\Enums\EventType;
use App\Models\Product;
use App\Models\ProductEvent;
use App\Models\QrCode;
use App\Models\Scopes\CompanyScope;
use App\Services\VisitorContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordProductEvent implements ShouldQueue
{
    use Queueable;

    public const UNIQUE_WINDOW_MINUTES = 30;

    /** The IP arrives already hashed: a raw IP never reaches the queue payload. */
    public function __construct(
        public int $productId,
        public EventType $type,
        public string $ipHash,
        public ?string $userAgent,
        public string $occurredAt,
    ) {}

    public function handle(): void
    {
        // Jobs run without an authenticated user, so the tenant scope is bypassed explicitly.
        $product = Product::withoutGlobalScope(CompanyScope::class)->withTrashed()->find($this->productId);

        if (! $product) {
            return;
        }

        $isUnique = ! ProductEvent::withoutGlobalScope(CompanyScope::class)
            ->where('product_id', $product->id)
            ->where('type', $this->type)
            ->where('ip_hash', $this->ipHash)
            ->where('created_at', '>=', now()->parse($this->occurredAt)->subMinutes(self::UNIQUE_WINDOW_MINUTES))
            ->exists();

        ProductEvent::withoutGlobalScope(CompanyScope::class)->create([
            'company_id' => $product->company_id,
            'branch_id' => $product->branch_id,
            'product_id' => $product->id,
            'type' => $this->type,
            'ip_hash' => $this->ipHash,
            'device_type' => VisitorContext::deviceType($this->userAgent),
            'user_agent' => $this->userAgent ? Str::limit($this->userAgent, 497) : null,
            'is_unique' => $isUnique,
            'created_at' => $this->occurredAt,
        ]);

        if ($this->type === EventType::Scan) {
            QrCode::where('product_id', $product->id)->update([
                'scans_count' => DB::raw('scans_count + 1'),
                'last_scanned_at' => $this->occurredAt,
            ]);
        }
    }
}
