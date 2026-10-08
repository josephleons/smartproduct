<?php

namespace App\Models;

use App\Enums\DeviceType;
use App\Enums\EventType;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductEvent extends Model
{
    use BelongsToCompany, HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['company_id', 'branch_id', 'product_id', 'type', 'ip_hash', 'device_type', 'user_agent', 'is_unique', 'created_at'];

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'device_type' => DeviceType::class,
            'is_unique' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
