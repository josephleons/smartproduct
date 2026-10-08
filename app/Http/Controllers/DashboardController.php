<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Enums\ProductStatus;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductEvent;
use App\Services\AnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AnalyticsService $analytics): View
    {
        $user = $request->user();
        $products = Product::query()->visibleTo($user);
        $from = CarbonImmutable::today()->subDays(6);

        return view('dashboard.index', [
            'stats' => [
                'products' => (clone $products)->count(),
                'available' => (clone $products)->whereIn('status', [ProductStatus::Available, ProductStatus::LowStock])->count(),
                'scans' => ProductEvent::query()
                    ->where('type', EventType::Scan)
                    ->when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                    ->where('created_at', '>=', CarbonImmutable::today()->subDays(29))
                    ->count(),
                'branches' => Branch::query()->availableTo($user)->active()->count(),
            ],
            'daily' => $analytics->daily($user, $from, 7),
            'topProducts' => $analytics->topProducts($user, CarbonImmutable::today()->subDays(29)),
            'recentProducts' => (clone $products)->with(['branch', 'category'])->latest()->limit(6)->get(),
        ]);
    }
}
