<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Not tenant-scoped with BelongsToCompany: system categories (company_id null)
 * are shared by every company. Use scopeVisibleTo() in the dashboard.
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'slug', 'icon'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function specFields(): HasMany
    {
        return $this->hasMany(CategorySpecField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function isSystem(): bool
    {
        return $this->company_id === null;
    }

    public function scopeVisibleTo(Builder $query, int $companyId): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('company_id')->orWhere('company_id', $companyId));
    }
}
