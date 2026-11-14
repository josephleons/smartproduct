<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;

/** The Lipa Namba a customer pays to, per mobile money network: the branch's, else the company's. */
class LipaNamba
{
    /** @return array<string, array{name: string, color: string, text: string, number: string}> networks that have a number */
    public static function for(Company $company, ?Branch $branch = null): array
    {
        $numbers = array_merge(
            array_filter((array) $company->payment_numbers),
            array_filter((array) $branch?->payment_numbers),
        );

        $networks = [];
        foreach (config('payments.networks') as $key => $network) {
            if (! empty($numbers[$key])) {
                $networks[$key] = $network + ['number' => (string) $numbers[$key]];
            }
        }

        return $networks;
    }

    /** Validation rules for the Lipa Namba inputs in company and branch settings. */
    public static function rules(): array
    {
        $rules = ['payment_numbers' => ['nullable', 'array:'.implode(',', array_keys(config('payments.networks')))]];
        foreach (array_keys(config('payments.networks')) as $key) {
            $rules["payment_numbers.{$key}"] = ['nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-z][0-9A-Za-z \-]*$/'];
        }

        return $rules;
    }

    /** Trimmed numbers without empty entries, or null when none are set. */
    public static function clean(?array $numbers): ?array
    {
        $numbers = array_filter(array_map(fn ($n) => is_string($n) ? trim($n) : null, (array) $numbers));

        return $numbers ?: null;
    }
}
