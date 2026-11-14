<?php

use App\Http\Controllers\Admin\BillingSettingsController as AdminBillingSettingsController;
use App\Http\Controllers\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryFieldsController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ProductQuickUpdateController;
use App\Http\Controllers\PublicProductController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
| Public product page. The QR on every printed sticker points here, so the
| "/p/{token}" format must never change.
*/
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/p/{token}', [PublicProductController::class, 'show'])->name('public.product.show');
    Route::get('/p/{token}/go/{channel}', [PublicProductController::class, 'go'])
        ->whereIn('channel', ['whatsapp', 'call'])
        ->name('public.product.go');
    Route::get('/p/{token}/similar', [PublicProductController::class, 'similar'])->name('public.product.similar');
});

// Login, logout and password reset routes come from Laravel Fortify
// (see config/fortify.php). There is no self-registration: companies are
// created by the platform superadmin below.

Route::redirect('/', '/dashboard');

Route::get('/language/{locale}', LocaleController::class)->name('locale.switch');

Route::middleware(['auth', 'tenant', 'subscription'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Bulk import, registered before the resource so "import" is not taken for a product id.
    Route::controller(ProductImportController::class)->prefix('products/import')->name('products.import.')->group(function () {
        Route::get('/', 'create')->name('create');
        Route::get('/template', 'template')->name('template');
        Route::post('/', 'store')->name('store');
        Route::get('/{import}', 'show')->whereUuid('import')->name('show');
        Route::post('/{import}', 'confirm')->whereUuid('import')->name('confirm');
        Route::get('/{import}/result', 'result')->whereUuid('import')->name('result');
    });

    Route::resource('products', ProductController::class);
    Route::patch('/products/{product}/quick', ProductQuickUpdateController::class)->name('products.quick-update');

    Route::get('/categories/{category}/fields', CategoryFieldsController::class)->name('categories.fields');

    Route::get('/labels', [LabelController::class, 'index'])->name('labels.index');
    Route::get('/labels/products', [LabelController::class, 'search'])->name('labels.products');
    Route::get('/labels/preview', [LabelController::class, 'preview'])->name('labels.preview');
    Route::get('/labels/pdf', [LabelController::class, 'pdf'])->name('labels.pdf');

    Route::resource('branches', BranchController::class)->except('show');

    Route::get('/analytics', AnalyticsController::class)->middleware('feature:analytics')->name('analytics.index');

    Route::prefix('settings')->group(function () {
        Route::get('/company', [CompanySettingsController::class, 'edit'])->name('settings.company.edit');
        Route::put('/company', [CompanySettingsController::class, 'update'])->name('settings.company.update');
        Route::resource('users', UserController::class)->except('show');
        Route::resource('categories', CategoryController::class)->except('show')
            ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'feature:custom_categories');

        Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
        Route::post('/billing/invoices/{invoice}/payments', [BillingController::class, 'submitPayment'])->name('billing.payments.store');
    });
});

// Platform admin (superadmin only): company onboarding.
Route::middleware(['auth', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('companies', AdminCompanyController::class)->except(['show', 'destroy']);
    Route::resource('plans', AdminPlanController::class)->except(['show', 'destroy']);

    Route::get('/billing/settings', [AdminBillingSettingsController::class, 'edit'])->name('billing.settings.edit');
    Route::put('/billing/settings', [AdminBillingSettingsController::class, 'update'])->name('billing.settings.update');

    Route::get('/invoices', [AdminInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [AdminInvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{invoice}/payments', [AdminInvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
    Route::post('/payments/{payment}/confirm', [AdminInvoiceController::class, 'confirm'])->name('payments.confirm');
    Route::post('/payments/{payment}/reject', [AdminInvoiceController::class, 'reject'])->name('payments.reject');
});
