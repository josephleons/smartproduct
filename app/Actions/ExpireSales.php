<?php

namespace App\Actions;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\Scopes\CompanyScope;

/** Marks payments nobody reviewed within config('payments.pending_expiry_hours') as expired. */
class ExpireSales
{
    public function handle(): int
    {
        // Runs from the scheduler with no tenant user: covers every company on purpose.
        return Sale::withoutGlobalScope(CompanyScope::class)
            ->where('status', SaleStatus::Pending)
            ->where('created_at', '<=', Sale::expiryCutoff())
            ->update(['status' => SaleStatus::Expired, 'updated_at' => now()]);
    }
}
