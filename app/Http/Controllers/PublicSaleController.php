<?php

namespace App\Http\Controllers;

use App\Actions\RequestSale;
use App\Http\Requests\PaySaleRequest;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Scopes\CompanyScope;
use App\Services\LipaNamba;
use App\Services\PublicProductLookup;
use App\Services\VisitorContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * "Pay for this item" from the QR page: the customer pays cash or to the shop's
 * Lipa Namba, then confirms here. Staff approve it in Payments. No login, no JS.
 */
class PublicSaleController extends Controller
{
    public function create(string $token): Response|RedirectResponse
    {
        $product = PublicProductLookup::find($token) ?? abort(response()->view('public.not-found', [], 404));

        if (! $product->status->showsPrice()) {
            return redirect()->route('public.product.show', $product->qrCode->token);
        }

        return response()->view('public.pay', [
            'product' => $product,
            'token' => $product->qrCode->token,
            'networks' => LipaNamba::for($product->company, $product->branch),
        ]);
    }

    public function store(PaySaleRequest $request, string $token, RequestSale $requestSale): RedirectResponse
    {
        $product = PublicProductLookup::find($token) ?? abort(response()->view('public.not-found', [], 404));

        if (! $product->status->showsPrice()) {
            return redirect()->route('public.product.show', $product->qrCode->token);
        }

        $sale = $requestSale->handle($product, $request->validated(), VisitorContext::hashIp($request->ip()));

        return redirect()->route('public.sale.status', [$product->qrCode->token, $sale->reference]);
    }

    public function status(string $token, string $reference): Response
    {
        $product = PublicProductLookup::find($token) ?? abort(response()->view('public.not-found', [], 404));

        // Public page: no tenant user, so the sale is matched through the product explicitly.
        $sale = Sale::withoutGlobalScope(CompanyScope::class)
            ->where('product_id', $product->id)
            ->where('reference', strtoupper($reference))
            ->first() ?? abort(response()->view('public.not-found', [], 404));

        return response()->view('public.pay-status', [
            'product' => $product,
            'token' => $product->qrCode->token,
            'sale' => $sale,
        ]);
    }
}
