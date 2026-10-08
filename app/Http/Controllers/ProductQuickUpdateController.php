<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuickUpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

class ProductQuickUpdateController extends Controller
{
    public function __invoke(QuickUpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return back()->with('success', __('Price and availability updated.'));
    }
}
