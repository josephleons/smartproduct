<?php

namespace App\Services\Billing;

use App\Enums\PlanFeature;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\Scopes\CompanyScope;

/** Plan scope of usage: resource limits and features for a company. */
class PlanLimits
{
    public const RESOURCES = ['products', 'branches', 'users'];

    public function count(Company $company, string $resource): int
    {
        return match ($resource) {
            'products' => Product::withoutGlobalScope(CompanyScope::class)->where('company_id', $company->id)->count(),
            'branches' => Branch::withoutGlobalScope(CompanyScope::class)->where('company_id', $company->id)->count(),
            'users' => $company->users()->count(),
        };
    }

    public function limit(Company $company, string $resource): ?int
    {
        return $company->subscription?->plan?->limit($resource);
    }

    public function reached(Company $company, string $resource): bool
    {
        $limit = $this->limit($company, $resource);

        return $limit !== null && $this->count($company, $resource) >= $limit;
    }

    public function hasFeature(Company $company, PlanFeature $feature): bool
    {
        return (bool) $company->subscription?->plan?->hasFeature($feature);
    }

    /** @return array<string, array{used: int, limit: int|null}> */
    public function usage(Company $company): array
    {
        return collect(self::RESOURCES)->mapWithKeys(fn ($r) => [
            $r => ['used' => $this->count($company, $r), 'limit' => $this->limit($company, $r)],
        ])->all();
    }

    public function message(string $resource, int $limit): string
    {
        return __('Your plan allows up to :limit :resource. Upgrade your plan to add more.', [
            'limit' => $limit,
            'resource' => __($resource),
        ]);
    }
}
