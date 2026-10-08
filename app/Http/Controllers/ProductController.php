<?php

namespace App\Http\Controllers;

use App\Actions\CreateProduct;
use App\Actions\UpdateProduct;
use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Http\Requests\ProductRequest;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Services\QrCodeRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);
        $user = $request->user();

        $products = Product::query()
            ->visibleTo($user)
            ->with(['branch', 'category', 'qrCode'])
            ->when($request->string('q')->trim()->value(), function ($query, string $search) {
                $query->where(fn ($q) => $q
                    ->where('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%"));
            })
            ->when($request->integer('category'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->integer('branch'), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            ...$this->formOptions($request),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Product::class);

        if ($limited = $this->planLimitResponse($request->user()->company, 'products', 'products.index')) {
            return $limited;
        }

        return view('products.form', [
            'product' => new Product([
                'condition' => ProductCondition::New,
                'status' => ProductStatus::Available,
                'quantity' => 1,
                'branch_id' => $request->user()->branch_id,
            ]),
            ...$this->formOptions($request),
        ]);
    }

    public function store(ProductRequest $request, CreateProduct $createProduct): RedirectResponse
    {
        $this->authorize('create', Product::class);

        if ($limited = $this->planLimitResponse($request->user()->company, 'products', 'products.index')) {
            return $limited;
        }

        $product = $createProduct->handle($request->user(), $request->validated());

        return redirect()->route('products.show', $product)->with('success', __('Product saved. Its QR label is ready to print.'));
    }

    public function show(Product $product, QrCodeRenderer $qr): View
    {
        $this->authorize('view', $product);

        $product->load(['branch', 'category', 'specs', 'qrCode']);

        return view('products.show', [
            'product' => $product,
            'qrSvg' => $product->qrCode ? $qr->svg($product->qrCode->url(), 220) : null,
            'statuses' => ProductStatus::cases(),
        ]);
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorize('update', $product);

        $product->load('specs');

        return view('products.form', [
            'product' => $product,
            ...$this->formOptions($request),
        ]);
    }

    public function update(ProductRequest $request, Product $product, UpdateProduct $updateProduct): RedirectResponse
    {
        $this->authorize('update', $product);

        $updateProduct->handle($product, $request->validated());

        return redirect()->route('products.show', $product)->with('success', __('Product updated. Printed labels show the new details automatically.'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('products.index')->with('success', __('Product deleted.'));
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request): array
    {
        $user = $request->user();

        return [
            'categories' => Category::visibleTo($user->company_id)->orderBy('name')->get(),
            'branches' => Branch::query()->availableTo($user)->active()->orderBy('name')->get(),
            'conditions' => ProductCondition::cases(),
            'statuses' => ProductStatus::cases(),
        ];
    }
}
