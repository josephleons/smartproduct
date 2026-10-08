<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return $this->sameCompany($user, $product) && $user->canAccessBranch($product->branch_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Product $product): bool
    {
        return $this->view($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->canManage() && $this->view($user, $product);
    }

    private function sameCompany(User $user, Product $product): bool
    {
        return $user->company_id === $product->company_id;
    }
}
