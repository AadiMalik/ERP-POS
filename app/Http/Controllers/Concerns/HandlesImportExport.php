<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ImportExport\Engine\ExportBuilderService;
use App\Services\ImportExport\Engine\ImportWriterService;
use App\Services\ImportExport\Engine\PreviewBuilderService;
use App\Services\ImportExport\Engine\SampleFileGeneratorService;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\ImportExportAuditService;
use App\Support\ImportExport\ImportExportModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Generic Import/Export actions, added to each of the ~25 module
 * controllers alongside their existing CRUD actions. The controller only
 * needs to declare importExportModuleKey() and gate these actions with
 * permission:{module}.import / permission:{module}.export middleware.
 */
trait HandlesImportExport
{
    abstract protected function importExportModuleKey(): string;

    public function importSample()
    {
        $def = ImportExportModuleRegistry::resolve($this->importExportModuleKey());

        return app(SampleFileGeneratorService::class)->download($def);
    }

    public function importPreview(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:10240']);

        $def = ImportExportModuleRegistry::resolve($this->importExportModuleKey());
        $ctx = ImportContext::fromRequest($request, $this->importExportModuleKey());

        if (!$def->canImport()) {
            return response()->json(['status' => false, 'message' => "Import is not available for {$def->label()}."], 422);
        }

        if ($def->isBusinessScoped() && !$ctx->businessId) {
            return response()->json(['status' => false, 'message' => 'Please select a business before importing (use the Business filter above).'], 422);
        }

        try {
            $batch = app(PreviewBuilderService::class)->stageAndPreview($request->file('file'), $def, $ctx);

            return response()->json([
                'status' => true,
                'batch_id' => $batch->import_batch_id,
                'summary' => $batch->summary_json,
            ]);
        } catch (Throwable $e) {
            app(ImportExportAuditService::class)->logImportFailed($def->moduleKey(), $def->label(), null, $e->getMessage(), $ctx);

            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function importConfirm(Request $request)
    {
        $request->validate(['batch_id' => 'required|uuid|exists:import_batches,import_batch_id']);

        $def = ImportExportModuleRegistry::resolve($this->importExportModuleKey());

        try {
            $summary = app(ImportWriterService::class)->confirm($request->batch_id, Auth::id());

            return response()->json(['status' => true, 'summary' => $summary]);
        } catch (Throwable $e) {
            $ctx = ImportContext::fromRequest($request, $this->importExportModuleKey());
            app(ImportExportAuditService::class)->logImportFailed($def->moduleKey(), $def->label(), $request->batch_id, $e->getMessage(), $ctx);

            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function export(Request $request)
    {
        $def = ImportExportModuleRegistry::resolve($this->importExportModuleKey());
        $ctx = ImportContext::fromRequest($request, $this->importExportModuleKey());

        app(ImportExportAuditService::class)->logExport($def->moduleKey(), $def->label(), $ctx, $request->all());

        return app(ExportBuilderService::class)->export($def, $request->all(), $ctx);
    }
}
