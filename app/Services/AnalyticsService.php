<?php

namespace App\Services;

use App\Enums\EventType;
use App\Models\Branch;
use App\Models\ProductEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Scan analytics. All queries run through ProductEvent's tenant scope. */
class AnalyticsService
{
    /**
     * @param  array{days?: int, branch_id?: int|null, category_id?: int|null}  $filters
     * @return array<string, mixed>
     */
    public function summary(User $user, array $filters): array
    {
        $days = $filters['days'] ?? 30;
        $from = CarbonImmutable::today()->subDays($days - 1);

        // All headline counts in one pass over the period's events instead of one query each.
        $totals = $this->events($user, $from, $filters)
            ->toBase()
            ->selectRaw(
                'SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS scans,
                 SUM(CASE WHEN type = ? AND is_unique = 1 THEN 1 ELSE 0 END) AS unique_scans,
                 COUNT(DISTINCT CASE WHEN type = ? THEN product_id END) AS products_scanned,
                 SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS whatsapp,
                 SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS calls,
                 SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS similar',
                [
                    EventType::Scan->value, EventType::Scan->value, EventType::Scan->value,
                    EventType::WhatsappClick->value, EventType::CallClick->value, EventType::SimilarClick->value,
                ],
            )
            ->first();

        // SUM() is NULL when no rows match.
        $scans = (int) $totals->scans;
        $uniqueScans = (int) $totals->unique_scans;
        $productsScanned = (int) $totals->products_scanned;
        $whatsapp = (int) $totals->whatsapp;
        $calls = (int) $totals->calls;
        $similar = (int) $totals->similar;

        return [
            'from' => $from,
            'days' => $days,
            'scans' => $scans,
            'unique_scans' => $uniqueScans,
            'products_scanned' => $productsScanned,
            'whatsapp_clicks' => $whatsapp,
            'call_clicks' => $calls,
            'similar_clicks' => $similar,
            'contact_rate' => $uniqueScans > 0 ? round(($whatsapp + $calls) / $uniqueScans * 100, 1) : 0.0,
            'daily' => $this->daily($user, $from, $days, $filters),
            'by_branch' => $this->byBranch($user, $from, $filters),
            'top_products' => $this->topProducts($user, $from, $filters),
        ];
    }

    /** @return Collection<int, array{date: string, label: string, scans: int}> */
    public function daily(User $user, CarbonImmutable $from, int $days, array $filters = []): Collection
    {
        $counts = $this->events($user, $from, $filters)
            ->where('type', EventType::Scan)
            ->selectRaw('DATE(product_events.created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, $days - 1))->map(function (int $offset) use ($from, $counts) {
            $date = $from->addDays($offset);

            return [
                'date' => $date->toDateString(),
                'label' => $date->format('d M'),
                'scans' => (int) ($counts[$date->toDateString()] ?? 0),
            ];
        });
    }

    /** @return Collection<int, array{name: string, scans: int}> */
    public function byBranch(User $user, CarbonImmutable $from, array $filters = []): Collection
    {
        $counts = $this->events($user, $from, $filters)
            ->where('type', EventType::Scan)
            ->selectRaw('product_events.branch_id, COUNT(*) as total')
            ->groupBy('product_events.branch_id')
            ->pluck('total', 'product_events.branch_id');

        return Branch::query()
            ->availableTo($user)
            ->orderBy('name')
            ->get()
            ->map(fn (Branch $branch) => ['name' => $branch->name, 'scans' => (int) ($counts[$branch->id] ?? 0)])
            ->sortByDesc('scans')
            ->values();
    }

    /** @return Collection<int, ProductEvent> with product loaded and a "scans" attribute */
    public function topProducts(User $user, CarbonImmutable $from, array $filters = [], int $limit = 5): Collection
    {
        return $this->events($user, $from, $filters)
            ->where('type', EventType::Scan)
            ->selectRaw('product_events.product_id, COUNT(*) as scans')
            ->groupBy('product_events.product_id')
            ->orderByDesc('scans')
            ->limit($limit)
            ->with('product.branch')
            ->get();
    }

    private function events(User $user, CarbonImmutable $from, array $filters): Builder
    {
        $branchId = $user->branch_id ?? ($filters['branch_id'] ?? null);

        return ProductEvent::query()
            ->where('product_events.created_at', '>=', $from)
            ->when($branchId, fn (Builder $q) => $q->where('product_events.branch_id', $branchId))
            ->when($filters['category_id'] ?? null, fn (Builder $q, $categoryId) => $q->whereHas(
                'product',
                fn (Builder $p) => $p->where('category_id', $categoryId)
            ));
    }
}
