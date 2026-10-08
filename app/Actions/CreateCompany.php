<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\SubscriptionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Superadmin onboarding: a company, its first branch and its owner account. */
class CreateCompany
{
    public function __construct(private SubscriptionManager $subscriptions) {}

    /**
     * @param  array{name: string, phone?: string|null, whatsapp?: string|null, email?: string|null, address?: string|null,
     *               branch_name: string, branch_location?: string|null,
     *               owner_name: string, owner_email: string, owner_password: string, plan_id: int}  $data
     */
    public function handle(array $data): Company
    {
        return DB::transaction(function () use ($data) {
            $company = Company::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'phone' => $data['phone'] ?? null,
                'whatsapp' => ($data['whatsapp'] ?? null) ?: ($data['phone'] ?? null),
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'is_active' => true,
            ]);

            Branch::create([
                'company_id' => $company->id,
                'name' => $data['branch_name'],
                'location' => $data['branch_location'] ?? null,
            ]);

            User::create([
                'company_id' => $company->id,
                'branch_id' => null,
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'password' => $data['owner_password'],
                'role' => UserRole::Owner,
            ]);

            // Starts the trial (or bills immediately when trial_days is 0).
            $this->subscriptions->start($company, Plan::findOrFail($data['plan_id']));

            return $company;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'company';
        $slug = $base;
        $i = 2;

        while (Company::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
