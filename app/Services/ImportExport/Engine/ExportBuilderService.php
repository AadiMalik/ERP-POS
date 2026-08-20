<?php

namespace App\Services\ImportExport\Engine;

use App\Exports\GenericImportExportModuleExport;
use App\Services\ImportExport\Contracts\ImportExportDefinitionContract;
use App\Services\ImportExport\Support\ImportContext;
use Maatwebsite\Excel\Facades\Excel;

class ExportBuilderService
{
    public function export(ImportExportDefinitionContract $def, array $filters, ImportContext $ctx)
    {
        $query = $def->exportQuery($filters, $ctx);

        if (!empty($def->exportEagerLoads())) {
            $query->with($def->exportEagerLoads());
        }

        $query = applyRoleScope($query, [], 'business_id', 'branch_id');

        $export = new GenericImportExportModuleExport($query, $def);
        $filename = $def->moduleKey() . '-export-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download($export, $filename);
    }
}
