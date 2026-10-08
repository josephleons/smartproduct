<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isOwner() && $user->company_id === $model->company_id;
    }

    public function delete(User $user, User $model): bool
    {
        return $this->update($user, $model) && $user->id !== $model->id;
    }
}
