<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'logo_path', 'phone', 'whatsapp', 'email', 'address', 'payment_numbers', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'payment_numbers' => 'array'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * The tenant scope is bypassed because this relation is read from the
     * superadmin area and from billing jobs; the foreign key already limits it.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->withoutGlobalScope(CompanyScope::class);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
