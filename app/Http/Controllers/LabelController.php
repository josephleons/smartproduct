<?php

namespace App\Http\Controllers;

use App\Http\Requests\LabelRequest;
use App\Models\Product;
use App\Services\LabelRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LabelController extends Controller
{
    public function __construct(private LabelRenderer $renderer) {}

    public function index(Request $request): View
    {
        $this->authorize('print-labels');

        return view('labels.index', [
            'products' => Product::query()->visibleTo($request->user())->latest()->limit(500)->get(['id', 'brand', 'model', 'sku']),
            'selected' => array_map('intval', (array) $request->input('products', [])),
            'templates' => config('labels.templates'),
            'defaultTemplate' => config('labels.default'),
            'fields' => config('labels.fields'),
        ]);
    }

    /** HTML preview, rendered from the same view as the PDF. */
    public function preview(LabelRequest $request): View
    {
        $this->authorize('print-labels');

        return view('labels.sheet', $this->viewData($request) + ['forPdf' => false]);
    }

    public function pdf(LabelRequest $request): Response
    {
        $this->authorize('print-labels');

        return $this->renderer->pdf($this->viewData($request))->download('smartlabel-labels-'.now()->format('Ymd-His').'.pdf');
    }

    /** @return array<string, mixed> */
    private function viewData(LabelRequest $request): array
    {
        return $this->renderer->viewData(
            $this->products($request),
            $request->validated('template'),
            $request->validated('layout'),
            $request->validated('fields') ?? [],
            (int) ($request->validated('copies') ?? 1),
        );
    }

    /** @return Collection<int, Product> */
    private function products(LabelRequest $request): Collection
    {
        $ids = $request->validated('products');

        $products = Product::query()
            ->visibleTo($request->user())
            ->with(['company', 'branch', 'specs', 'qrCode'])
            ->whereKey($ids)
            ->get()
            ->sortBy(fn (Product $p) => array_search($p->id, $ids))
            ->values();

        abort_if($products->isEmpty(), 404);

        return $products;
    }
}
