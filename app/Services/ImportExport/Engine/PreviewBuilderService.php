<?php

namespace App\Services\ImportExport\Engine;

use App\Models\ImportBatch;
use App\Services\ImportExport\Contracts\ImportExportDefinitionContract;
use App\Services\ImportExport\Support\BatchSummary;
use App\Services\ImportExport\Support\ImportContext;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * Orchestrates preview: parse -> group by the module's group key -> resolve
 * each group via the Definition's resolveRow() -> assemble a BatchSummary.
 * Used both by the Preview step and, re-run fresh, at the start of Confirm
 * (per the "re-validate against current DB state" decision).
 */
class PreviewBuilderService
{
    public function __construct(protected FileParserService $fileParser)
    {
    }

    public function stageAndPreview(UploadedFile $file, ImportExportDefinitionContract $def, ImportContext $ctx): ImportBatch
    {
        $batchId = generateUuid();
        $relativeDir = 'imports/' . ($ctx->businessId ?? 'unassigned');
        $filename = $batchId . '.' . strtolower($file->getClientOriginalExtension());
        $file->storeAs($relativeDir, $filename);
        $relativePath = $relativeDir . '/' . $filename;

        $batch = ImportBatch::create([
            'import_batch_id' => $batchId,
            'business_id' => $ctx->businessId,
            'branch_id' => $ctx->branchId,
            'module_key' => $def->moduleKey(),
            'uploaded_by_id' => $ctx->userId,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $relativePath,
            'status' => 'pending_preview',
            'date_created' => now(),
        ]);

        try {
            $summary = $this->buildFromFile(storage_path('app/' . $relativePath), $def, $ctx);
        } catch (Throwable $e) {
            $batch->update(['status' => 'failed', 'error_message' => $e->getMessage(), 'date_updated' => now()]);
            throw $e;
        }

        $batch->update([
            'status' => 'previewed',
            'row_count' => $summary->rowCount,
            'valid_count' => $summary->validCount,
            'invalid_count' => $summary->invalidCount,
            'create_count' => $summary->createCount,
            'update_count' => $summary->updateCount,
            'summary_json' => $summary->toArray(),
            'date_updated' => now(),
        ]);

        return $batch;
    }

    public function buildFromFile(string $absoluteFilePath, ImportExportDefinitionContract $def, ImportContext $ctx): BatchSummary
    {
        $parsed = $this->fileParser->parse($absoluteFilePath, $def->maxRows());
        $groups = $this->group($parsed['rows'], $def->groupKeyColumn());

        $resolved = [];
        foreach ($groups as $group) {
            $resolved[] = $def->resolveRow($group, $ctx);
        }

        return BatchSummary::fromGroups($def->moduleKey(), $resolved);
    }

    protected function group(array $rows, ?string $groupKeyColumn): array
    {
        if (!$groupKeyColumn) {
            return array_map(fn ($row) => ['group_key' => null, 'rows' => [$row]], $rows);
        }

        $groups = [];
        $order = [];

        foreach ($rows as $row) {
            $key = trim((string) ($row['raw'][$groupKeyColumn] ?? ''));
            if ($key === '') {
                // No group key on this row - treat as its own singleton group
                // rather than silently merging it into another group.
                $key = '__row_' . $row['row_number'];
            }
            if (!isset($groups[$key])) {
                $groups[$key] = ['group_key' => $key, 'rows' => []];
                $order[] = $key;
            }
            $groups[$key]['rows'][] = $row;
        }

        return array_map(fn ($k) => $groups[$k], $order);
    }
}
