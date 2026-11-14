<?php

namespace App\Models;

use App\Enums\SaleMethod;
use App\Enums\SaleStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A customer's payment confirmation from the QR page. SmartLabel records it;
 * the money itself goes to the shop (cash or Lipa Namba). Staff approve it,
 * which takes one unit of the product off stock.
 */
class Sale extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id', 'branch_id', 'product_id', 'reference', 'method', 'network', 'payment_number',
        'transaction_code', 'customer_name', 'customer_phone', 'listed_price', 'amount',
        'received_amount', 'status', 'reviewed_by', 'reviewed_at', 'note', 'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'method' => SaleMethod::class,
            'status' => SaleStatus::class,
            'listed_price' => 'integer',
            'amount' => 'integer',
            'received_amount' => 'integer',
            'reviewed_at' => 'datetime',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function networkName(): ?string
    {
        return $this->network ? (config("payments.networks.{$this->network}.name") ?? $this->network) : null;
    }

    /** Pending and still inside the expiry window. */
    public function isOpen(): bool
    {
        return $this->status === SaleStatus::Pending && $this->created_at->gt(self::expiryCutoff());
    }

    public static function expiryCutoff(): Carbon
    {
        return now()->subHours((int) config('payments.pending_expiry_hours', 24));
    }

    /** Sales a user may see: everything in the company, or one branch for branch-restricted staff. */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->when($user->branch_id, fn (Builder $q) => $q->where('sales.branch_id', $user->branch_id));
    }

    public function scopeOpen(Builder $query): void
    {
        $query->where('status', SaleStatus::Pending)->where('created_at', '>', self::expiryCutoff());
    }
}
