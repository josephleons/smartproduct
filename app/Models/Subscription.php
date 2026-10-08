<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = ['company_id', 'plan_id', 'status', 'paid_until', 'trial_ends_at', 'grace_ends_at'];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'paid_until' => 'date',
            'trial_ends_at' => 'date',
            'grace_ends_at' => 'date',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** Scope bypassed: the foreign key already limits rows, and admins/jobs read this without a tenant. */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->withoutGlobalScope(CompanyScope::class);
    }

    public function openInvoice(): ?Invoice
    {
        return $this->invoices()->whereIn('status', InvoiceStatus::open())->oldest('period_start')->first();
    }

    public function allowsWrites(): bool
    {
        return $this->status->allowsWrites();
    }
}
