<?php

namespace App\Models;

use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'category_id', 'brand', 'model', 'description', 'condition',
        'price', 'quantity', 'serial_number', 'warranty_months', 'status', 'sku',
    ];

    protected function casts(): array
    {
        return [
            'condition' => ProductCondition::class,
            'status' => ProductStatus::class,
            'price' => 'integer',
            'quantity' => 'integer',
            'warranty_months' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function specs(): HasMany
    {
        return $this->hasMany(ProductSpec::class)->orderBy('sort_order')->orderBy('id');
    }

    public function qrCode(): HasOne
    {
        return $this->hasOne(QrCode::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProductEvent::class);
    }

    public function name(): string
    {
        return trim($this->brand.' '.$this->model);
    }

    /** Short spec line for labels, e.g. "Core i5 · 16 GB · 512GB SSD". */
    public function labelSpecLine(int $limit = 4): string
    {
        return $this->specs
            ->where('show_on_label', true)
            ->take($limit)
            ->map(fn (ProductSpec $spec) => $spec->displayValue())
            ->implode(' · ');
    }

    public function publicUrl(): ?string
    {
        return $this->qrCode?->url();
    }

    /** Products a user may see: everything in the company, or one branch for branch-restricted staff. */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->when($user->branch_id, fn (Builder $q) => $q->where('branch_id', $user->branch_id));
    }
}
