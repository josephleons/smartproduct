<?php

namespace App\Services\ProductImport;

use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Enums\SpecFieldType;
use App\Models\Category;
use App\Models\CategorySpecField;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * The columns of a category's import sheet: the product's own fields, then the
 * category's spec fields. Writes the downloadable template and maps the header
 * row of an uploaded file back to column keys (in English or Swahili).
 */
class ImportTemplate
{
    /** Product columns, in sheet order. Spec columns follow, keyed "specs.<key>". */
    public const BASE_COLUMNS = [
        'brand' => ['label' => 'Brand', 'required' => true, 'example' => 'Dell'],
        'model' => ['label' => 'Model', 'required' => true, 'example' => 'Latitude 5420'],
        'price' => ['label' => 'Price (TZS)', 'required' => true, 'example' => 850000],
        'condition' => ['label' => 'Condition', 'required' => false, 'example' => 'Grade A'],
        'status' => ['label' => 'Status', 'required' => false, 'example' => 'Available'],
        'quantity' => ['label' => 'Quantity', 'required' => false, 'example' => 1],
        'serial_number' => ['label' => 'Serial number', 'required' => false, 'example' => 'SN-0001'],
        'warranty_months' => ['label' => 'Warranty (months)', 'required' => false, 'example' => 3],
        'description' => ['label' => 'Notes for customers', 'required' => false, 'example' => ''],
    ];

    public function __construct(private Category $category) {}

    /** @return array<string, array{label: string, required: bool, example: mixed, field: CategorySpecField|null}> */
    public function columns(): array
    {
        $columns = [];

        foreach (self::BASE_COLUMNS as $key => $column) {
            $columns[$key] = $column + ['field' => null];
        }

        foreach ($this->category->specFields as $field) {
            $columns['specs.'.$field->key] = [
                'label' => $field->label,
                'required' => $field->is_required,
                'example' => $this->example($field),
                'field' => $field,
            ];
        }

        return $columns;
    }

    /** Header text shown in the sheet, e.g. "RAM (GB) *". */
    public function header(string $key, ?string $locale = null): string
    {
        $column = $this->columns()[$key];
        $unit = $column['field']?->type === SpecFieldType::Number && $column['field']->unit
            ? ' ('.__($column['field']->unit, [], $locale).')'
            : '';

        return __($column['label'], [], $locale).$unit.($column['required'] ? ' *' : '');
    }

    /**
     * Map each header cell to a column key, accepting the key itself or the label in
     * any app language, with or without unit and "*". A name can fit several columns
     * (in Swahili both Condition and Status are "Hali"): each such header takes the
     * first of those columns, in template order, that is not taken yet.
     *
     * @param  list<mixed>  $headerRow
     * @return array<int, string> column index => column key
     */
    public function mapHeaders(array $headerRow): array
    {
        $lookup = [];

        foreach ($this->columns() as $key => $column) {
            $names = [$key, str_replace('specs.', '', $key), $column['label']];
            foreach (array_keys(config('app.locales')) as $locale) {
                $names[] = __($column['label'], [], $locale);
                $names[] = $this->header($key, $locale);
            }
            foreach (array_unique(array_map(self::normalize(...), $names)) as $name) {
                $lookup[$name][] = $key;
            }
        }

        $map = [];
        foreach ($headerRow as $index => $cell) {
            $candidates = $lookup[self::normalize((string) $cell)] ?? [];
            $key = collect($candidates)->first(fn ($k) => ! in_array($k, $map, true));
            if ($key !== null) {
                $map[$index] = $key;
            }
        }

        return $map;
    }

    /** @return list<string> keys of required columns missing from a header map */
    public function missingRequired(array $headerMap): array
    {
        return array_values(array_diff(
            array_keys(array_filter($this->columns(), fn ($c) => $c['required'])),
            $headerMap,
        ));
    }

    /** Write the template to $path: header row, one example row, and (xlsx) a sheet of allowed values. */
    public function write(string $path, string $format): void
    {
        $columns = $this->columns();
        $headers = array_map(fn ($key) => $this->header($key), array_keys($columns));
        $example = array_map(fn ($c) => $c['example'], array_values($columns));

        if ($format === 'csv') {
            $writer = new CsvWriter;
            $writer->openToFile($path);
            $writer->addRow(Row::fromValues($headers));
            $writer->addRow(Row::fromValues($example));
            $writer->close();

            return;
        }

        $writer = new XlsxWriter;
        $writer->openToFile($path);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName(mb_substr(__($this->category->name), 0, 31));
        $sheet->setColumnWidth(22, ...range(1, count($headers)));
        $writer->addRow(Row::fromValues($headers, (new Style)->setFontBold()->setBackgroundColor('EAF4F7')));
        $writer->addRow(Row::fromValues($example, (new Style)->setFontItalic()));

        $help = $writer->addNewSheetAndMakeItCurrent();
        $help->setName(mb_substr(__('Allowed values'), 0, 31));
        $help->setColumnWidth(28, 1);
        $help->setColumnWidth(70, 2);
        $writer->addRow(Row::fromValues([__('Column'), __('What to enter')], (new Style)->setFontBold()));
        foreach ($this->helpRows() as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();
    }

    /** @return list<array{0: string, 1: string}> */
    private function helpRows(): array
    {
        $rows = [
            [__('Instructions'), __('Fill one product per row, starting on row 2. Replace or delete the example row. Columns marked * are required.')],
            [$this->header('price'), __('Whole shillings, e.g. 850000 or 850,000.')],
            [$this->header('condition'), implode(', ', array_map(fn ($c) => $c->label(), ProductCondition::cases())).'. '.__('Empty means New.')],
            [$this->header('status'), implode(', ', array_map(fn ($s) => $s->label(), ProductStatus::cases())).'. '.__('Empty means Available.')],
            [$this->header('quantity'), __('Empty means 1.')],
            [$this->header('serial_number'), __('Optional. Each serial number can be registered once. Format the column as Text in Excel for long numeric serials.')],
        ];

        foreach ($this->category->specFields as $field) {
            $rows[] = [$this->header('specs.'.$field->key), match ($field->type) {
                SpecFieldType::Select => implode(', ', $field->options ?? []),
                SpecFieldType::Boolean => __('Yes').' / '.__('No'),
                SpecFieldType::Number => __('A number').($field->unit ? ' ('.__($field->unit).')' : ''),
                SpecFieldType::Text => __('Text'),
            }];
        }

        return $rows;
    }

    private function example(CategorySpecField $field): mixed
    {
        return match ($field->type) {
            SpecFieldType::Select => $field->options[0] ?? '',
            SpecFieldType::Boolean => __('Yes'),
            SpecFieldType::Number => 8,
            SpecFieldType::Text => '',
        };
    }

    /** "RAM (GB) *" → "ram", "Serial_number" → "serialnumber". */
    public static function normalize(string $text): string
    {
        $text = preg_replace('/\([^)]*\)/u', '', $text);

        return preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($text));
    }
}
