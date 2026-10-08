<?php

namespace App\Services\Billing;

use App\Models\PlatformSetting;

/** Platform billing configuration: config/billing.php defaults, overridden by the superadmin. */
class BillingSettings
{
    private const PREFIX = 'billing.';

    /** @return array<string, mixed> */
    public function all(): array
    {
        $stored = PlatformSetting::where('key', 'like', self::PREFIX.'%')->pluck('value', 'key')
            ->mapWithKeys(fn ($value, $key) => [substr($key, strlen(self::PREFIX)) => $value])
            ->all();

        return array_replace(config('billing.settings'), $stored);
    }

    public function get(string $key): mixed
    {
        return $this->all()[$key] ?? null;
    }

    /** @param  array<string, mixed>  $values */
    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            PlatformSetting::updateOrCreate(['key' => self::PREFIX.$key], ['value' => $value]);
        }
    }
}
