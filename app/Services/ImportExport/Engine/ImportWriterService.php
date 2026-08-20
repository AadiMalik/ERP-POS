<?php

namespace App\Services\ImportExport\Engine;

use App\Models\ImportBatch;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\ImportExportAuditService;
use App\Support\ImportExport\ImportExportModuleRegistry;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Confirm-time writer: re-parses the staged file fresh (not trusting the
 * stale preview blob), then writes each valid row-group in its own
 * transaction so one bad group doesn't roll back the whole batch.
 */
class ImportWriterService
{
    public function __construct(
        protected PreviewBuilderService $previewBuilder,
        protected ImportExportAuditService $auditService,
    ) {
    }

    public function confirm(string $batchId, $userId): array
    {
        $batch = ImportBatch::where('import_batch_id', $batchId)->where('is_deleted', 0)->firstOrFail();

        if (!in_array($batch->status, ['pending_preview', 'previewed'], true)) {
            throw new Exception('This import batch has already been processed.');
        }

        if ($batch->date_created && now()->diffInHours($batch->date_created) > 24) {
            $batch->update(['status' => 'expired', 'date_updated' => now()]);
            throw new Exception('This import session has expired. Please re-upload the file.');
        }

        $def = ImportExportModuleRegistry::resolve($batch->module_key);

        if (!$def->canImport()) {
            throw new Exception("Import is not available for {$def->label()}.");
        }

        $ctx = new ImportContext($batch->module_key, $batch->business_id, $batch->branch_id, $userId);

        $absolutePath = storage_path('app/' . $batch->file_path);
        $summary = $this->previewBuilder->buildFromFile($absolutePath, $def, $ctx);

        $createCount = 0;
        $updateCount = 0;
        $failedCount = 0;

        foreach ($summary->groups as $group) {
            if ($group->action === 'invalid') {
                continue;
            }

            DB::beginTransaction();
            try {
                $result = $def->save($group, $ctx);
                $result['created'] ? $createCount++ : $updateCount++;
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                $failedCount++;
                $group->errors[] = ['column' => null, 'value' => null, 'reason' => $e->getMessage()];
            }
        }

        $batch->update([
            'status' => 'confirmed',
            'create_count' => $createCount,
            'update_count' => $updateCount,
            'failed_count' => $failedCount,
            'invalid_count' => $summary->invalidCount,
            'summary_json' => $summary->toArray(),
            'date_updated' => now(),
        ]);

        $this->auditService->logImport($def, $batch, $summary, $createCount, $updateCount, $failedCount, $ctx);

        return [
            'total_rows' => $summary->rowCount,
            'created' => $createCount,
            'updated' => $updateCount,
            'invalid' => $summary->invalidCount,
            'failed' => $failedCount,
        ];
    }
}
