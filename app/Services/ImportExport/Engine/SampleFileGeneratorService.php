<?php

namespace App\Services\ImportExport\Engine;

use App\Exports\ImportExport\SampleDataSheet;
use App\Exports\ImportExport\SampleInstructionsSheet;
use App\Services\ImportExport\Contracts\ImportExportDefinitionContract;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;

class SampleFileGeneratorService
{
    public function download(ImportExportDefinitionContract $def)
    {
        $export = new class($def) implements WithMultipleSheets {
            public function __construct(protected ImportExportDefinitionContract $def)
            {
            }

            public function sheets(): array
            {
                return [
                    new SampleDataSheet($this->def),
                    new SampleInstructionsSheet($this->def),
                ];
            }
        };

        return Excel::download($export, $def->moduleKey() . '-import-sample.xlsx');
    }
}
