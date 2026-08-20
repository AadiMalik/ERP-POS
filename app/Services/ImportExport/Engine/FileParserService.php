<?php

namespace App\Services\ImportExport\Engine;

use App\Services\ImportExport\Exceptions\ImportRowCapExceededException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Parses the first sheet of an uploaded/staged xlsx into a header list + an
 * array of {row_number, raw} rows (raw keyed by header text), skipping fully
 * blank rows and enforcing the module's row cap.
 */
class FileParserService
{
    public function parse(string $absoluteFilePath, int $maxRows): array
    {
        $sheets = Excel::toArray([], $absoluteFilePath);
        $sheet = $sheets[0] ?? [];

        if (empty($sheet)) {
            return ['headers' => [], 'rows' => []];
        }

        $headerRow = array_map(fn ($h) => trim((string) $h), array_shift($sheet));

        $rows = [];
        $rowNumber = 1;

        foreach ($sheet as $rawRow) {
            $rowNumber++;

            if ($this->isBlankRow($rawRow)) {
                continue;
            }

            $assoc = [];
            foreach ($headerRow as $index => $headerName) {
                if ($headerName === '') {
                    continue;
                }
                $assoc[$headerName] = $rawRow[$index] ?? null;
            }

            $rows[] = ['row_number' => $rowNumber, 'raw' => $assoc];

            if (count($rows) > $maxRows) {
                throw new ImportRowCapExceededException("This file exceeds the maximum of {$maxRows} data rows. Please split it into smaller files and import them separately.");
            }
        }

        return ['headers' => $headerRow, 'rows' => $rows];
    }

    protected function isBlankRow(array $rawRow): bool
    {
        foreach ($rawRow as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
