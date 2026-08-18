<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Enums\Filter;
use App\Models\Asset;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class AssetService
{
    protected $model_asset;

    public function __construct()
    {
        $this->model_asset = new Repository(new Asset());
    }

    public function getData($obj)
    {
        $wh = [];
        if (isset($obj['status']) && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }

        $datatable = $this->model_asset->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->with(['currentAllocation.employee.user'])
            ->orderBy('date_created', Filter::ORDERBY);
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('status', function ($item) {
                $map = ['available' => 'success', 'allocated' => 'info', 'maintenance' => 'warning', 'retired' => 'secondary'];
                return '<span class="badge bg-label-' . ($map[$item->status] ?? 'secondary') . '">' . ucfirst($item->status) . '</span>';
            })
            ->addColumn('allocated_to', function ($item) {
                return $item->currentAllocation?->employee?->user?->name ?? '-';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('asset.edit', $item->asset_id) . "'>
                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteAsset'
                    data-id='{$item->asset_id}'>
                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        if (!empty($obj['asset_id'])) {
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $this->model_asset->update($obj, $obj['asset_id']);
            return $this->model_asset->find($obj['asset_id']);
        }

        $obj['asset_id'] = generateUuid();
        $obj['status'] = 'available';
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();
        return $this->model_asset->create($obj);
    }

    public function getById($asset_id)
    {
        return $this->model_asset->find($asset_id);
    }

    public function delete($asset_id)
    {
        return $this->model_asset->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $asset_id);
    }

    public function getAvailable()
    {
        $query = $this->model_asset->getModel()::where('is_deleted', 0)->where('status', 'available');
        return applyRoleScope($query)->orderBy('name')->get();
    }

    public function setStatus($asset_id, $status)
    {
        return $this->model_asset->update(['status' => $status, 'date_updated' => now()], $asset_id);
    }
}
