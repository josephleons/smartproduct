<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /** Platform admin: list and onboard companies. */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /** Platform admin: edit any company, including activating or deactivating it. */
    public function manage(User $user, Company $company): bool
    {
        return $user->isSuperAdmin();
    }

    /** Company owner editing their own profile in Settings. */
    public function update(User $user, Company $company): bool
    {
        return $user->isOwner() && $user->company_id === $company->id;
    }
}
