<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManage();
    }

    /** Reading a template (e.g. loading spec fields in the product wizard). */
    public function view(User $user, Category $category): bool
    {
        return $category->isSystem() || $category->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->canManage();
    }

    /** System categories are shared and read-only. */
    public function update(User $user, Category $category): bool
    {
        return $user->canManage() && ! $category->isSystem() && $category->company_id === $user->company_id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->update($user, $category);
    }
}
