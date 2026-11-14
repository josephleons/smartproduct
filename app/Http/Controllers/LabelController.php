<?php

namespace App\Http\Controllers;

use App\Http\Requests\LabelRequest;
use App\Models\Product;
use App\Services\LabelRenderer;
use Illuminate\Http\JsonResponse;
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

        // Only the preselected products are rendered; the picker searches the rest on demand.
        $selected = array_map('intval', (array) $request->input('products', []));

        return view('labels.index', [
            'selected' => Product::query()
                ->visibleTo($request->user())
                ->whereKey(array_slice($selected, 0, 200))
                ->get(['id', 'brand', 'model', 'sku'])
                ->sortBy(fn (Product $p) => array_search($p->id, $selected))
                ->values(),
            'templates' => config('labels.templates'),
            'defaultTemplate' => config('labels.default'),
            'fields' => config('labels.fields'),
        ]);
    }

    /** Product picker search, in the shape select2 expects: { results, pagination: { more } }. */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('print-labels');

        $page = Product::query()
            ->visibleTo($request->user())
            ->search($request->string('q')->value())
            ->latest()
            ->simplePaginate(20, ['id', 'brand', 'model', 'sku']);

        return response()->json([
            'results' => $page->getCollection()->map(fn (Product $p) => [
                'id' => $p->id,
                'text' => $p->name().' ('.$p->sku.')',
            ]),
            'pagination' => ['more' => $page->hasMorePages()],
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
