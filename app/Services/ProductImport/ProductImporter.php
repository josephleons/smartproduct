<?php

namespace App\Services\ProductImport;

use App\Actions\CreateProduct;
use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Enums\SpecFieldType;
use App\Models\Category;
use App\Models\CategorySpecField;
use App\Models\Product;
use App\Models\User;
use App\Services\Billing\PlanLimits;
use App\Services\ProductRules;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;

/**
 * Bulk product import for one category and branch, in two steps:
 *
 *  1. preview(): read the file, normalise each row the way sellers type it
 *     ("850,000", "16GB", "grade a", "ndiyo"), validate it with the same rules as
 *     the Add Product form, and keep the result in the cache for the seller to review.
 *  2. import(): re-check the stored rows (things may have changed meanwhile) and
 *     create the valid ones through CreateProduct, up to the plan's product limit.
 */
class ProductImporter
{
    use Localizable;

    /** @var array<class-string, array<string, string>> normalised name => enum value */
    private array $enumNames = [];

    public const MAX_ROWS = 1000;

    public const TTL_SECONDS = 7200;

    public function __construct(
        private SpreadsheetReader $reader,
        private CreateProduct $createProduct,
        private PlanLimits $limits,
    ) {}

    /**
     * @return string id of the stored preview
     *
     * @throws ImportFileException
     */
    public function preview(User $user, Category $category, int $branchId, UploadedFile $file): string
    {
        $category->loadMissing('specFields');
        $template = new ImportTemplate($category);
        $sheet = $this->reader->read($file->getRealPath(), $file->getClientOriginalExtension(), self::MAX_ROWS);

        $headerMap = $template->mapHeaders($sheet['header']);
        if ($missing = $template->missingRequired($headerMap)) {
            throw new ImportFileException(__('The file is missing these columns: :columns. Download the :category template and copy your products into it.', [
                'columns' => implode(', ', array_map(fn ($key) => $template->header($key), $missing)),
                'category' => __($category->name),
            ]));
        }

        if ($sheet['rows'] === []) {
            throw new ImportFileException(__('The file has no products. Add one product per row below the header.'));
        }

        $rows = [];
        foreach ($sheet['rows'] as $number => $cells) {
            $rows[$number] = $this->normalize($category, $branchId, $headerMap, $cells);
        }

        [$valid, $errors] = $this->check($user, $category, $rows);

        $ignored = collect($sheet['header'])
            ->reject(fn ($cell, $index) => isset($headerMap[$index]) || $cell === null || $cell === '')
            ->map(fn ($cell) => (string) $cell)
            ->values()
            ->all();

        $id = (string) Str::uuid();
        Cache::put(self::cacheKey($id), [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'category_id' => $category->id,
            'branch_id' => $branchId,
            'file_name' => $file->getClientOriginalName(),
            'total' => count($rows),
            'rows' => $valid,
            'errors' => $errors,
            'ignored_columns' => $ignored,
        ], self::TTL_SECONDS);

        return $id;
    }

    /** The stored preview, only for the user who uploaded it. */
    public function find(User $user, string $id): ?array
    {
        $pending = Cache::get(self::cacheKey($id));

        return is_array($pending) && $pending['user_id'] === $user->id && $pending['company_id'] === $user->company_id
            ? $pending
            : null;
    }

    /**
     * Create the previewed products. The preview is consumed first so a double submit
     * cannot import twice.
     *
     * @return array{created: list<int>, failed: list<array{row: int, messages: list<string>}>, over_limit: int}|null
     */
    public function import(User $user, string $id): ?array
    {
        return Cache::lock(self::cacheKey($id).':lock', 120)->get(function () use ($user, $id) {
            $pending = $this->find($user, $id);
            if (! $pending) {
                return null;
            }
            Cache::forget(self::cacheKey($id));

            $category = Category::visibleTo($user->company_id)->with('specFields')->find($pending['category_id']);
            if (! $category) {
                return ['created' => [], 'failed' => [], 'over_limit' => 0];
            }

            // Re-check: a branch may have been closed or a serial registered since the preview.
            [$valid, $failed] = $this->check($user, $category, $pending['rows']);

            $limit = $this->limits->limit($user->company, 'products');
            $room = $limit === null ? count($valid) : max(0, $limit - $this->limits->count($user->company, 'products'));
            $toCreate = array_slice($valid, 0, $room, true);

            // One transaction: an unexpected failure leaves no half-imported file behind.
            $created = DB::transaction(fn () => array_values(array_map(
                fn (array $data) => $this->createProduct->handle($user, $data)->id,
                $toCreate,
            )));

            return ['created' => $created, 'failed' => $failed, 'over_limit' => count($valid) - count($toCreate)];
        }) ?: null; // lock held by a simultaneous submit of the same import
    }

    public static function cacheKey(string $id): string
    {
        return 'product-import:'.$id;
    }

    /**
     * Validate normalised rows like the Add Product form, plus serial numbers that are
     * repeated in the file or already registered.
     *
     * @param  array<int, array<string, mixed>>  $rows  keyed by spreadsheet row number
     * @return array{0: array<int, array<string, mixed>>, 1: list<array{row: int, messages: list<string>}>}
     */
    private function check(User $user, Category $category, array $rows): array
    {
        $fields = $category->specFields;
        $rules = ProductRules::rules($user, $fields);
        $attributes = ProductRules::attributes($fields);
        $messages = $this->selectMessages($fields);

        $serials = collect($rows)->pluck('serial_number')->filter()->map(fn ($s) => (string) $s)->unique()->values();
        // Tenant scope applies: only this company's live products count as duplicates.
        $registered = $serials->isEmpty() ? collect() : Product::query()
            ->whereIn('serial_number', $serials)
            ->pluck('sku', 'serial_number');

        $valid = [];
        $errors = [];
        $seen = [];

        foreach ($rows as $number => $data) {
            $rowErrors = Validator::make($data, $rules, $messages, $attributes)->errors()->all();
            $serial = $data['serial_number'] ?? null;

            if ($serial !== null && $serial !== '') {
                if (isset($registered[$serial])) {
                    $rowErrors[] = __('Serial number :serial is already registered (:sku).', ['serial' => $serial, 'sku' => $registered[$serial]]);
                } elseif (isset($seen[$serial])) {
                    $rowErrors[] = __('Serial number :serial is also on row :row.', ['serial' => $serial, 'row' => $seen[$serial]]);
                }
                $seen[$serial] ??= $number;
            }

            if ($rowErrors) {
                $errors[] = ['row' => $number, 'messages' => $rowErrors];
            } else {
                $valid[$number] = $data;
            }
        }

        return [$valid, $errors];
    }

    /** "Operating system must be one of: Windows 11, Windows 10, …" instead of "is invalid". */
    private function selectMessages(Collection $fields): array
    {
        return $fields
            ->filter(fn (CategorySpecField $f) => $f->type === SpecFieldType::Select)
            ->mapWithKeys(fn (CategorySpecField $f) => [
                'specs.'.$f->key.'.in' => __(':attribute must be one of: :values.', ['values' => implode(', ', $f->options ?? [])]),
            ])
            ->all();
    }

    /**
     * Turn one sheet row into the same shape as the Add Product form submits.
     *
     * @param  array<int, string>  $headerMap  column index => column key
     * @param  list<mixed>  $cells
     * @return array<string, mixed>
     */
    private function normalize(Category $category, int $branchId, array $headerMap, array $cells): array
    {
        $raw = [];
        foreach ($headerMap as $index => $key) {
            $raw[$key] = $cells[$index] ?? null;
        }

        $data = [
            'category_id' => $category->id,
            'branch_id' => $branchId,
            'brand' => $this->text($raw['brand'] ?? null),
            'model' => $this->text($raw['model'] ?? null),
            'price' => $this->money($raw['price'] ?? null),
            'condition' => $this->enum(ProductCondition::class, $raw['condition'] ?? null) ?? ProductCondition::New->value,
            'status' => $this->enum(ProductStatus::class, $raw['status'] ?? null) ?? ProductStatus::Available->value,
            'quantity' => $this->integer($raw['quantity'] ?? null) ?? 1,
            'serial_number' => $this->text($raw['serial_number'] ?? null),
            'warranty_months' => $this->integer($raw['warranty_months'] ?? null),
            'description' => $this->text($raw['description'] ?? null),
            'specs' => [],
        ];

        foreach ($category->specFields as $field) {
            $value = $this->spec($field, $raw['specs.'.$field->key] ?? null);
            if ($value !== null) {
                $data['specs'][$field->key] = $value;
            }
        }

        return $data;
    }

    private function text(mixed $value): ?string
    {
        $value = match (true) {
            $value === null => null,
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            is_bool($value) => $value ? '1' : '0',
            // Excel stores long numeric serials as floats: print without exponent or ".0".
            is_float($value) && floor($value) === $value => sprintf('%.0f', $value),
            default => trim((string) $value),
        };

        return $value === '' ? null : $value;
    }

    /** "TZS 850,000", "850 000" or 850000.0 → 850000; anything else is left for validation to reject. */
    private function money(mixed $value): mixed
    {
        if (is_int($value) || is_float($value)) {
            return (int) round($value);
        }

        $text = $this->text($value);
        if ($text === null) {
            return null;
        }

        $clean = preg_replace('/^(tzs|tsh|sh)\.?|[\s,]/i', '', $text);

        return is_numeric($clean) ? (int) round((float) $clean) : $text;
    }

    private function integer(mixed $value): mixed
    {
        $text = $this->text($value);

        return $text !== null && is_numeric($text) && floor((float) $text) == $text ? (int) $text : $text;
    }

    /**
     * Accepts the stored value ("grade_a") or the label in any app language ("Grade A", "Daraja A").
     *
     * @param  class-string<BackedEnum>  $enum
     */
    private function enum(string $enum, mixed $value): ?string
    {
        $text = $this->text($value);
        if ($text === null) {
            return null;
        }

        $this->enumNames[$enum] ??= collect(array_keys(config('app.locales')))
            ->flatMap(fn (string $locale) => $this->withLocale($locale, fn () => collect($enum::cases())
                ->mapWithKeys(fn ($case) => [ImportTemplate::normalize($case->label()) => $case->value])))
            ->merge(collect($enum::cases())->mapWithKeys(fn ($case) => [ImportTemplate::normalize($case->value) => $case->value]))
            ->all();

        return $this->enumNames[$enum][ImportTemplate::normalize($text)] ?? $text; // unknown: validation reports it
    }

    private function spec(CategorySpecField $field, mixed $value): mixed
    {
        $text = $this->text($value);
        if ($text === null) {
            return null;
        }

        return match ($field->type) {
            SpecFieldType::Boolean => match (ImportTemplate::normalize($text)) {
                'yes', 'y', 'true', '1', 'ndiyo', 'ndio' => '1',
                'no', 'n', 'false', '0', 'hapana' => '0',
                default => $text,
            },
            SpecFieldType::Select => collect($field->options ?? [])
                ->first(fn ($option) => ImportTemplate::normalize($option) === ImportTemplate::normalize($text)) ?? $text,
            // "16GB", "16 GB" or "95%" → the number; the unit comes from the template.
            SpecFieldType::Number => $this->number($text, $field->unit),
            SpecFieldType::Text => $text,
        };
    }

    private function number(string $text, ?string $unit): string
    {
        $clean = str_replace([',', ' '], '', $text);
        if ($unit) {
            $clean = preg_replace('/'.preg_quote(str_replace(' ', '', $unit), '/').'$/i', '', $clean);
        }
        $clean = rtrim($clean, '%');

        return is_numeric($clean) ? $clean : $text;
    }
}
