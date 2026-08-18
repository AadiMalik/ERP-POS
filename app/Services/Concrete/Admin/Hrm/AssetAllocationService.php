<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Models\Asset;
use App\Models\AssetAllocation;
use App\Models\Employee;
use App\Traits\Auditable;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class AssetAllocationService
{
    use Auditable;

    protected $asset_service;

    public function __construct(AssetService $asset_service)
    {
        $this->asset_service = $asset_service;
    }

    public function getData($obj)
    {
        $wh = [];
        if (isset($obj['employee_id']) && $obj['employee_id'] != "") {
            $wh[] = ['employee_id', $obj['employee_id']];
        }
        if (isset($obj['status']) && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }

        $datatable = AssetAllocation::where($wh)
            ->with(['asset', 'employee.user'])
            ->orderBy('date_created', 'desc');
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('asset', function ($item) {
                return $item->asset?->name ?? '-';
            })
            ->addColumn('employee', function ($item) {
                return $item->employee?->user?->name ?? '-';
            })
            ->addColumn('status', function ($item) {
                $map = ['issued' => 'info', 'returned' => 'success', 'lost' => 'danger', 'damaged' => 'warning'];
                return '<span class="badge bg-label-' . ($map[$item->status] ?? 'secondary') . '">' . ucfirst($item->status) . '</span>';
            })
            ->addColumn('action', function ($item) {
                $actions = '';
                if ($item->status == 'issued') {
                    $actions .= "<a class='btn btn-icon btn-outline-success' id='returnAllocation' data-id='{$item->asset_allocation_id}'><i class='fa fa-undo'></i></a>";
                }
                return $actions;
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function issue($obj)
    {
        $asset = Asset::findOrFail($obj['asset_id']);
        if ($asset->status != 'available') {
            throw new Exception('This asset is not currently available for allocation.');
        }
        $employee = Employee::findOrFail($obj['employee_id']);

        DB::beginTransaction();
        try {
            $allocation = AssetAllocation::create([
                'asset_allocation_id' => generateUuid(),
                'asset_id' => $obj['asset_id'],
                'employee_id' => $obj['employee_id'],
                'issue_date' => $obj['issue_date'] ?? now()->toDateString(),
                'expected_return_date' => $obj['expected_return_date'] ?? null,
                'condition_on_issue' => $obj['condition_on_issue'] ?? 'good',
                'status' => 'issued',
                'remarks' => $obj['remarks'] ?? null,
                'business_id' => $employee->business_id,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);

            $this->asset_service->setStatus($obj['asset_id'], 'allocated');

            $this->logActivity('asset-allocation', $allocation->asset_allocation_id, 'issued', null, ['asset_id' => $obj['asset_id'], 'employee_id' => $obj['employee_id']], null, $employee->business_id, $employee->branch_id);

            DB::commit();
            return $allocation;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function returnAsset($asset_allocation_id, $condition_on_return, $remarks = null)
    {
        $allocation = AssetAllocation::findOrFail($asset_allocation_id);
        if ($allocation->status != 'issued') {
            throw new Exception('This allocation has already been closed.');
        }

        DB::beginTransaction();
        try {
            $is_lost = $condition_on_return == 'lost';
            $allocation->update([
                'return_date' => now()->toDateString(),
                'condition_on_return' => $is_lost ? null : $condition_on_return,
                'status' => $is_lost ? 'lost' : ($condition_on_return == 'damaged' ? 'damaged' : 'returned'),
                'remarks' => $remarks,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            $this->asset_service->setStatus($allocation->asset_id, $is_lost ? 'retired' : 'available');

            $this->logActivity('asset-allocation', $asset_allocation_id, 'returned', null, ['status' => $allocation->status]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getByEmployee($employee_id)
    {
        return AssetAllocation::with(['asset'])
            ->where('employee_id', $employee_id)
            ->orderBy('date_created', 'desc')
            ->get();
    }
}
