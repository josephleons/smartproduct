<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Enums\ProductStatus;
use App\Jobs\RecordProductEvent;
use App\Models\Product;
use App\Models\QrCode;
use App\Models\Scopes\CompanyScope;
use App\Services\PhoneNumber;
use App\Services\PublicProductCache;
use App\Services\VisitorContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The customer-facing page behind every QR sticker. Server-rendered, no
 * Vuexy, cached per token. Never writes to the database synchronously.
 */
class PublicProductController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $product = $this->findProduct($token);

        if (! $product) {
            return response()->view('public.not-found', [], 404);
        }

        $this->track($request, $product, EventType::Scan);

        $html = PublicProductCache::remember($product->qrCode->token, fn () => view('public.product', [
            'product' => $product,
            'token' => $product->qrCode->token,
            'showPrice' => $product->status->showsPrice(),
            'whatsapp' => $this->whatsappNumber($product),
            'phone' => $this->phoneNumber($product),
        ])->render());

        return response($html)->header('Cache-Control', 'public, max-age=60');
    }

    public function go(Request $request, string $token, string $channel): RedirectResponse|Response
    {
        $product = $this->findProduct($token);

        if (! $product) {
            return response()->view('public.not-found', [], 404);
        }

        $product->loadMissing(['company', 'branch']);

        if ($channel === 'whatsapp' && ($number = $this->whatsappNumber($product))) {
            $this->track($request, $product, EventType::WhatsappClick);
            $text = __('Hello, I am interested in :product (:sku). :url', [
                'product' => $product->name(),
                'sku' => $product->sku,
                'url' => $product->qrCode->url(),
            ]);

            return redirect()->away('https://wa.me/'.$number.'?text='.rawurlencode($text));
        }

        if ($channel === 'call' && ($number = $this->phoneNumber($product))) {
            $this->track($request, $product, EventType::CallClick);

            return redirect()->away('tel:+'.$number);
        }

        return redirect()->route('public.product.show', $token);
    }

    public function similar(Request $request, string $token): Response
    {
        $product = $this->findProduct($token);

        if (! $product) {
            return response()->view('public.not-found', [], 404);
        }

        $this->track($request, $product, EventType::SimilarClick);
        $product->loadMissing('company');

        // Public route: no tenant user, so filter by the product's company explicitly.
        $similar = Product::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $product->company_id)
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->whereIn('status', [ProductStatus::Available, ProductStatus::LowStock])
            ->whereHas('qrCode')
            ->with(['qrCode', 'branch' => fn ($q) => $q->withoutGlobalScope(CompanyScope::class)])
            ->orderByRaw('ABS(CAST(price AS SIGNED) - ?)', [$product->price])
            ->limit(8)
            ->get();

        return response()->view('public.similar', ['product' => $product, 'similar' => $similar, 'token' => $token]);
    }

    private function findProduct(string $token): ?Product
    {
        $token = strtoupper($token);

        if (! preg_match('/^[2-9A-HJ-NP-Z]{7}$/', $token)) {
            return null;
        }

        $qr = QrCode::where('token', $token)->first();

        // Public token lookup: no authenticated tenant, and a logged-in seller from another
        // company must still see this page, so the tenant scope is bypassed on purpose.
        return $qr ? Product::withoutGlobalScope(CompanyScope::class)
            ->with([
                'qrCode',
                'company',
                'branch' => fn ($q) => $q->withoutGlobalScope(CompanyScope::class),
                'specs',
                'category',
            ])
            ->find($qr->product_id) : null;
    }

    private function track(Request $request, Product $product, EventType $type): void
    {
        $userAgent = $request->userAgent();

        if (VisitorContext::isBot($userAgent)) {
            return;
        }

        RecordProductEvent::dispatch(
            $product->id,
            $type,
            VisitorContext::hashIp($request->ip()),
            $userAgent,
            now()->toDateTimeString(),
        );
    }

    private function whatsappNumber(Product $product): ?string
    {
        return PhoneNumber::international($product->branch?->whatsapp ?: $product->company->whatsapp ?: $product->branch?->phone ?: $product->company->phone);
    }

    private function phoneNumber(Product $product): ?string
    {
        return PhoneNumber::international($product->branch?->phone ?: $product->company->phone);
    }
}
