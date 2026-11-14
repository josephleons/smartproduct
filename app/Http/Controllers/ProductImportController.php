<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductImportRequest;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Services\Billing\PlanLimits;
use App\Services\ProductImport\ImportFileException;
use App\Services\ProductImport\ImportTemplate;
use App\Services\ProductImport\ProductImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Bulk product import: download a category template, upload it, review the
 * preview (ready rows and rows with errors), then import the ready rows.
 */
class ProductImportController extends Controller
{
    public function __construct(private ProductImporter $importer) {}

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Product::class);

        if ($limited = $this->planLimitResponse($request->user()->company, 'products', 'products.index')) {
            return $limited;
        }

        $user = $request->user();

        return view('products.import.create', [
            'categories' => Category::visibleTo($user->company_id)->orderBy('name')->get(),
            'branches' => Branch::query()->availableTo($user)->active()->orderBy('name')->get(),
            'maxRows' => ProductImporter::MAX_ROWS,
        ]);
    }

    public function template(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Product::class);

        $data = $request->validate([
            'category' => ['required', 'integer'],
            'format' => ['required', Rule::in(['xlsx', 'csv'])],
        ]);

        $category = Category::visibleTo($request->user()->company_id)->with('specFields')->findOrFail($data['category']);
        $path = tempnam(sys_get_temp_dir(), 'sl-template-');
        (new ImportTemplate($category))->write($path, $data['format']);

        return response()
            ->download($path, 'smartlabel-'.Str::slug(__($category->name)).'-template.'.$data['format'])
            ->deleteFileAfterSend();
    }

    public function store(ProductImportRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $user = $request->user();
        $category = Category::visibleTo($user->company_id)->with('specFields')->findOrFail($request->validated('category_id'));

        try {
            $id = $this->importer->preview($user, $category, (int) $request->validated('branch_id'), $request->file('file'));
        } catch (ImportFileException $e) {
            return back()->withInput()->withErrors(['file' => $e->getMessage()]);
        }

        return redirect()->route('products.import.show', $id);
    }

    public function show(Request $request, string $import, PlanLimits $limits): View
    {
        $pending = $this->importer->find($request->user(), $import) ?? abort(404);
        $company = $request->user()->company;
        $limit = $limits->limit($company, 'products');

        return view('products.import.show', [
            'import' => $import,
            'pending' => $pending,
            'category' => Category::visibleTo($company->id)->findOrFail($pending['category_id']),
            'branch' => Branch::query()->find($pending['branch_id']),
            'room' => $limit === null ? null : max(0, $limit - $limits->count($company, 'products')),
        ]);
    }

    public function confirm(Request $request, string $import): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $result = $this->importer->import($request->user(), $import);

        if ($result === null) {
            return redirect()->route('products.import.create')
                ->with('error', __('This import has expired or was already done. Upload the file again if products are missing.'));
        }

        Cache::put(self::resultKey($import), $result + ['user_id' => $request->user()->id], ProductImporter::TTL_SECONDS);

        return redirect()->route('products.import.result', $import);
    }

    public function result(Request $request, string $import): View
    {
        $result = Cache::get(self::resultKey($import));
        abort_unless(is_array($result) && $result['user_id'] === $request->user()->id, 404);

        return view('products.import.result', [
            'result' => $result,
            // Label printing takes up to 200 products per sheet run.
            'labelBatches' => array_chunk($result['created'], 200),
        ]);
    }

    private static function resultKey(string $import): string
    {
        return ProductImporter::cacheKey($import).':result';
    }
}
