<?php

namespace App\Actions;

use App\Enums\ProductStatus;
use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Staff approve or reject a customer's payment. Approving takes one unit off
 * stock; at zero the product becomes Sold, which hides the price on the QR page.
 */
class ReviewSale
{
    public function approve(Sale $sale, User $user, int $receivedAmount, ?string $note = null): Sale
    {
        return DB::transaction(function () use ($sale, $user, $receivedAmount, $note) {
            $sale = $this->lockOpen($sale);
            $product = Product::withTrashed()->lockForUpdate()->find($sale->product_id);

            if (! $product || $product->trashed() || ! $product->status->showsPrice()) {
                throw ValidationException::withMessages([
                    'sale' => __('This product is no longer available (sold, inactive or deleted), so the payment cannot be approved. Reject it and refund the customer if needed.'),
                ]);
            }

            $product->quantity = max(0, $product->quantity - 1);
            if ($product->quantity === 0) {
                $product->status = ProductStatus::Sold;
            }
            $product->save(); // ProductObserver clears the cached QR page

            $sale->update([
                'status' => SaleStatus::Approved,
                'received_amount' => $receivedAmount,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'note' => $note,
            ]);

            return $sale;
        });
    }

    public function reject(Sale $sale, User $user, ?string $note = null): Sale
    {
        return DB::transaction(function () use ($sale, $user, $note) {
            $sale = $this->lockOpen($sale);

            $sale->update([
                'status' => SaleStatus::Rejected,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'note' => $note,
            ]);

            return $sale;
        });
    }

    /** Re-read with a row lock so two staff cannot both approve the same payment. */
    private function lockOpen(Sale $sale): Sale
    {
        $locked = Sale::query()->lockForUpdate()->findOrFail($sale->id);

        if (! $locked->isOpen()) {
            throw ValidationException::withMessages(['sale' => match ($locked->status) {
                SaleStatus::Approved => __('This payment was already approved.'),
                SaleStatus::Rejected => __('This payment was already rejected.'),
                default => __('This payment request has expired.'),
            }]);
        }

        return $locked;
    }
}
