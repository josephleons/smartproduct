<?php

namespace App\Services\ProductImport;

use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Throwable;

/** Reads the first sheet of an .xlsx or .csv file into rows of plain cell values. */
class SpreadsheetReader
{
    /**
     * @return array{header: list<mixed>, rows: array<int, list<mixed>>} rows keyed by spreadsheet row number
     *
     * @throws ImportFileException when the file cannot be read or has too many rows
     */
    public function read(string $path, string $extension, int $maxRows): array
    {
        $reader = $this->reader($path, strtolower($extension));

        try {
            $reader->open($path);
            $header = null;
            $rows = [];

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $number => $row) {
                    $values = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $row->toArray());

                    if ($header === null) {
                        $header = $values;

                        continue;
                    }

                    if ($this->isBlank($values)) {
                        continue;
                    }

                    if (count($rows) >= $maxRows) {
                        throw new ImportFileException(__('The file has more than :max products. Split it into smaller files.', ['max' => $maxRows]));
                    }

                    $rows[$number] = $values;
                }

                break; // first sheet only; the template's second sheet is help text
            }
        } catch (ImportFileException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            throw new ImportFileException(__('The file could not be read. Upload the Excel (.xlsx) or CSV template.'));
        } finally {
            rescue(fn () => $reader->close(), report: false);
        }

        return ['header' => $header ?? [], 'rows' => $rows];
    }

    private function reader(string $path, string $extension): ReaderInterface
    {
        if ($extension === 'xlsx') {
            return new XlsxReader;
        }

        // Excel saves CSV with ";" in many regional settings: use whichever separator the header uses most.
        $handle = fopen($path, 'r');
        $firstLine = $handle ? (string) fgets($handle) : '';
        if ($handle) {
            fclose($handle);
        }

        $counts = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
        arsort($counts);

        $options = new CsvOptions;
        $options->FIELD_DELIMITER = (string) array_key_first($counts);

        return new CsvReader($options);
    }

    /** @param  list<mixed>  $values */
    private function isBlank(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }
}
