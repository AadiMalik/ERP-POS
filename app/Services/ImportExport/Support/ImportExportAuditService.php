<?php

namespace App\Services\ImportExport\Support;

use App\Models\ImportBatch;
use App\Services\ImportExport\Contracts\ImportExportDefinitionContract;
use App\Traits\Auditable;

/**
 * Writes one Activity Log entry per import batch (not per row, to avoid
 * flooding the log on a large import) via the existing Auditable trait -
 * the sole write path to ActivityLog used across the app.
 */
class ImportExportAuditService
{
    use Auditable;

    public function logImport(
        ImportExportDefinitionContract $def,
        ImportBatch $batch,
        BatchSummary $summary,
        int $createCount,
        int $updateCount,
        int $failedCount,
        ImportContext $ctx
    ): void {
        $hasErrors = $failedCount > 0 || $summary->invalidCount > 0;

        $this->logActivity(
            module: $def->moduleKey(),
            recordId: $batch->import_batch_id,
            action: $hasErrors ? 'import_completed_with_errors' : 'import_completed',
            old: null,
            new: [
                'file' => $batch->original_filename,
                'total_rows' => $summary->rowCount,
                'created' => $createCount,
                'updated' => $updateCount,
                'invalid' => $summary->invalidCount,
                'failed' => $failedCount,
                'errors' => $summary->errorSample(50),
            ],
            description: "Imported {$summary->rowCount} rows for {$def->label()} ({$createCount} created, {$updateCount} updated, " . ($summary->invalidCount + $failedCount) . ' failed).',
            businessId: $ctx->businessId,
            branchId: $ctx->branchId,
        );
    }

    public function logImportFailed(string $moduleKey, string $label, ?string $batchId, string $message, ImportContext $ctx): void
    {
        $this->logActivity(
            module: $moduleKey,
            recordId: $batchId,
            action: 'import_failed',
            old: null,
            new: null,
            description: "Import failed for {$label}: {$message}",
            businessId: $ctx->businessId,
            branchId: $ctx->branchId,
        );
    }

    public function logExport(string $moduleKey, string $label, ImportContext $ctx, ?array $filters = null): void
    {
        $this->logActivity(
            module: $moduleKey,
            recordId: null,
            action: 'exported',
            old: null,
            new: ['filters' => $filters],
            description: "Exported {$label} to Excel.",
            businessId: $ctx->businessId,
            branchId: $ctx->branchId,
        );
    }
}
