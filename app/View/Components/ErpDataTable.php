<?php

namespace App\View\Components;

use App\Support\DataTables\DataTableManager;
use Illuminate\View\Component;

/**
 * Single ERP listing DataTable. Index views only pass the registry key:
 * <x-erp-data-table key="customers" />
 */
class ErpDataTable extends Component
{
    public array $erpTable;

    public function __construct(string $key)
    {
        $this->erpTable = app(DataTableManager::class)->bootstrap($key);
    }

    public function render()
    {
        return view('components.erp-data-table');
    }
}
