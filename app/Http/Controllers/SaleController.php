<?php

namespace App\Http\Controllers;

use App\Actions\ReviewSale;
use App\Enums\SaleStatus;
use App\Http\Requests\ApproveSaleRequest;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Staff side of QR payments (installable as a PWA): scan a sticker or pick from
 * the list, check the customer's payment, enter the amount received, approve.
 */
class SaleController extends Controller
{
    public const TABS = ['pending', 'approved', 'rejected', 'expired', 'all'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Sale::class);
        $user = $request->user();
        $tab = in_array($request->query('status'), self::TABS, true) ? $request->query('status') : 'pending';

        $sales = Sale::query()
            ->visibleTo($user)
            ->with(['product.qrCode', 'branch', 'reviewer'])
            ->when($tab === 'pending', fn (Builder $q) => $q->open())
            // Pending past the deadline counts as expired even before the hourly sweep runs.
            ->when($tab === 'expired', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('status', SaleStatus::Expired)
                ->orWhere(fn (Builder $p) => $p->where('status', SaleStatus::Pending)->where('created_at', '<=', Sale::expiryCutoff()))))
            ->when(in_array($tab, ['approved', 'rejected'], true), fn (Builder $q) => $q->where('status', $tab))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('sales.index', [
            'sales' => $sales,
            'tab' => $tab,
            'pendingCount' => Sale::query()->visibleTo($user)->open()->count(),
        ]);
    }

    /** Manual entry fallback for the scanner: a sticker token or SKU typed by staff. */
    public function lookup(Request $request): RedirectResponse
    {
        $code = trim((string) $request->query('code'));

        return $code === ''
            ? redirect()->route('sales.index')
            : redirect()->route('sales.product', $code);
    }

    /** Opened by scanning a sticker: the device and its payments waiting for review. */
    public function product(Request $request, string $code): View|RedirectResponse
    {
        $code = strtoupper(trim($code));

        $product = Product::query()
            ->where(fn (Builder $q) => $q
                ->where('sku', $code)
                ->orWhereHas('qrCode', fn (Builder $qr) => $qr->where('token', $code)))
            ->with(['branch', 'category', 'specs', 'qrCode'])
            ->first();

        if (! $product) {
            return redirect()->route('sales.index')->with('error', __('No product of yours has the code :code.', ['code' => $code]));
        }

        $this->authorize('view', $product);

        return view('sales.product', [
            'product' => $product,
            'openSales' => $product->sales()->open()->latest()->get(),
            'history' => $product->sales()->with('reviewer')->where(fn (Builder $q) => $q
                ->where('status', '!=', SaleStatus::Pending)
                ->orWhere('created_at', '<=', Sale::expiryCutoff()))->latest()->limit(10)->get(),
        ]);
    }

    public function show(Sale $sale): View
    {
        $this->authorize('view', $sale);

        $sale->load(['product.qrCode', 'product.branch', 'reviewer']);

        return view('sales.show', ['sale' => $sale]);
    }

    public function approve(ApproveSaleRequest $request, Sale $sale, ReviewSale $review): RedirectResponse
    {
        $this->authorize('review', $sale);

        $review->approve($sale, $request->user(), (int) $request->validated('received_amount'), $request->validated('note'));
        $product = $sale->product()->first();

        return back()->with('success', $product->quantity > 0
            ? __('Payment :reference approved. :count left in stock.', ['reference' => $sale->reference, 'count' => $product->quantity])
            : __('Payment :reference approved. The product is now marked Sold.', ['reference' => $sale->reference]));
    }

    public function reject(Request $request, Sale $sale, ReviewSale $review): RedirectResponse
    {
        $this->authorize('review', $sale);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:255']]);

        $review->reject($sale, $request->user(), $data['note'] ?? null);

        return back()->with('success', __('Payment :reference rejected.', ['reference' => $sale->reference]));
    }
}
