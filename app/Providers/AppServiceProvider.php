<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Observers\ProductObserver;
use App\Observers\PublicPageDependencyObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Product::observe(ProductObserver::class);
        // Their details are shown on public product pages, which are cached.
        foreach ([Company::class, Branch::class, Category::class] as $model) {
            $model::observe(PublicPageDependencyObserver::class);
        }

        // Customer payment confirmations from QR pages, per visitor IP.
        RateLimiter::for('sale-requests', fn (Request $request) => Limit::perHour((int) config('payments.max_requests_per_hour', 10))->by($request->ip()));

        Gate::define('view-analytics', fn (User $user) => $user->canManage());
        Gate::define('print-labels', fn (User $user) => true);

        Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
            if ($src !== null) {
                return [
                    'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : ''),
                ];
            }

            return [];
        });
    }
}
