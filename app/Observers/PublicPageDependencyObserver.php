<?php

namespace App\Observers;

use App\Services\PublicProductCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Company, branch and category details appear on every public product page.
 * Saving one of them changes its version so cached pages showing it re-render.
 */
class PublicPageDependencyObserver
{
    public function saved(Model $model): void
    {
        PublicProductCache::bump($model);
    }

    public function deleted(Model $model): void
    {
        PublicProductCache::bump($model);
    }
}
