<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id', 'subscription_id', 'plan_id', 'number', 'plan_name', 'amount',
        'period_start', 'period_end', 'due_date', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'amount' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Invoice $invoice) {
            $invoice->updateQuietly(['number' => sprintf('INV-%s-%05d', $invoice->created_at->format('Ym'), $invoice->id)]);
        });
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** Scope bypassed: the foreign key already limits rows, and admins/jobs read this without a tenant. */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->withoutGlobalScope(CompanyScope::class)->latest();
    }

    public function confirmedAmount(): int
    {
        return (int) $this->payments()->where('status', PaymentStatus::Confirmed)->sum('amount');
    }

    public function balance(): int
    {
        return max(0, $this->amount - $this->confirmedAmount());
    }

    public function isOpen(): bool
    {
        return in_array($this->status, InvoiceStatus::open(), true);
    }
}
