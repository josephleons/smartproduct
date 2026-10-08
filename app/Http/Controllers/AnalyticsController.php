<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public const PERIODS = [7, 30, 90];

    public function __invoke(Request $request, AnalyticsService $analytics): View
    {
        $this->authorize('view-analytics');
        $user = $request->user();

        $days = in_array($request->integer('days'), self::PERIODS, true) ? $request->integer('days') : 30;
        $filters = [
            'days' => $days,
            'branch_id' => $request->integer('branch') ?: null,
            'category_id' => $request->integer('category') ?: null,
        ];

        return view('analytics.index', [
            'summary' => $analytics->summary($user, $filters),
            'filters' => $filters,
            'periods' => self::PERIODS,
            'branches' => Branch::query()->availableTo($user)->orderBy('name')->get(),
            'categories' => Category::visibleTo($user->company_id)->orderBy('name')->get(),
        ]);
    }
}
