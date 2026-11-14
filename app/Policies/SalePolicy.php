<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

/** Any staff role may review payments for the branches they can access. */
class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Sale $sale): bool
    {
        return $user->company_id === $sale->company_id && $user->canAccessBranch($sale->branch_id);
    }

    public function review(User $user, Sale $sale): bool
    {
        return $this->view($user, $sale);
    }
}
