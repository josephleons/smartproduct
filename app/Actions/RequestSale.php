<?php

namespace App\Actions;

use App\Enums\SaleMethod;
use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Scopes\CompanyScope;
use App\Services\LipaNamba;
use App\Services\PhoneNumber;
use App\Services\QrTokenGenerator;
use Illuminate\Database\UniqueConstraintViolationException;

/** A customer confirms on the QR page that they paid (cash or Lipa Namba). Staff approve it later. */
class RequestSale
{
    public const REFERENCE_LENGTH = 6;

    /** @param  array{method: string, network?: string|null, transaction_code?: string|null, customer_name: string, customer_phone: string, amount: int}  $data */
    public function handle(Product $product, array $data, string $ipHash): Sale
    {
        $method = SaleMethod::from($data['method']);
        $network = $method === SaleMethod::Mobile ? $data['network'] : null;

        $attributes = [
            // Public request: no tenant user, so company and branch come from the product.
            'company_id' => $product->company_id,
            'branch_id' => $product->branch_id,
            'product_id' => $product->id,
            'method' => $method,
            'network' => $network,
            'payment_number' => $network ? LipaNamba::for($product->company, $product->branch)[$network]['number'] ?? null : null,
            'transaction_code' => $method === SaleMethod::Mobile ? strtoupper(trim($data['transaction_code'])) : null,
            'customer_name' => trim($data['customer_name']),
            'customer_phone' => PhoneNumber::international($data['customer_phone']),
            'listed_price' => $product->price,
            'amount' => (int) $data['amount'],
            'status' => SaleStatus::Pending,
            'ip_hash' => $ipHash,
        ];

        for ($attempt = 1; ; $attempt++) {
            try {
                return Sale::withoutGlobalScope(CompanyScope::class)->create($attributes + ['reference' => $this->reference()]);
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= 3) {
                    throw $e;
                }
            }
        }
    }

    /** Short code the customer shows staff, from the unambiguous sticker alphabet. */
    private function reference(): string
    {
        $alphabet = QrTokenGenerator::ALPHABET;
        $reference = '';
        for ($i = 0; $i < self::REFERENCE_LENGTH; $i++) {
            $reference .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $reference;
    }
}
