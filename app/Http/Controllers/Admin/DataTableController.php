<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DataTables\DataTableManager;
use App\Support\DataTables\DataTablePreferenceService;
use App\Support\DataTables\DataTableRegistry;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Generic endpoints for every ERP DataTable registered in DataTableRegistry.
 * Listing screens only pass a table key to <x-erp-data-table />.
 */
class DataTableController extends Controller
{
    use ResponseAPI;

    public function __construct(
        protected DataTableManager $datatables,
        protected DataTablePreferenceService $preferenceService
    ) {
        $this->middleware('auth');
    }

    public function data(Request $request, string $key)
    {
        $this->assertKey($key);

        try {
            return $this->datatables->data($key, $request);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function loadPreferences(string $key)
    {
        $this->assertKey($key);

        try {
            if (DataTableRegistry::has($key)) {
                $definition = $this->datatables->definition($key);
                $this->datatables->authorize($definition);
                $state = $this->preferenceService->stateFor($definition);
            } else {
                $state = $this->preferenceService->rawState($key);
            }

            return $this->success(__('datatable.preferences_saved'), $state);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function savePreferences(Request $request, string $key)
    {
        $this->assertKey($key);

        try {
            if (DataTableRegistry::has($key)) {
                if ($request->boolean('reset')) {
                    $state = $this->datatables->resetPreferences($key);
                } else {
                    $state = $this->datatables->savePreferences($key, $request);
                }
            } elseif ($request->boolean('reset')) {
                $state = $this->preferenceService->resetRaw($key);
            } else {
                $state = $this->preferenceService->saveRaw($key, [
                    'visible_columns' => $request->input('visible_columns'),
                    'column_order' => $request->input('column_order'),
                    'sort_column' => $request->input('sort_column'),
                    'sort_dir' => $request->input('sort_dir'),
                    'page_length' => $request->input('page_length'),
                    'export_columns' => $request->input('export_columns'),
                    'filters' => $request->input('filters', []),
                ]);
            }

            return $this->success(__('datatable.preferences_saved'), $state);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function assertKey(string $key): void
    {
        abort_unless((bool) preg_match('/^[A-Za-z0-9_-]{1,80}$/', $key), 404);
    }
}
