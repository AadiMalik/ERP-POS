<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\LossReason;
use App\Repository\Repository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class LossReasonService
{
    protected $model_loss_reason;
    protected $with = ['business'];

    public function __construct()
    {
        $this->model_loss_reason = new Repository(new LossReason());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (isset($obj['business_id']) && $obj['business_id'] != 0 && $obj['business_id'] != "") {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::INVENTORYMANAGER,
            RoleNames::BRANCHADMIN,
        ];

        $datatable = $this->model_loss_reason->getModel()::where($wh)
            ->with($this->with)
            ->where('is_deleted', 0)
            ->orderBy('date_created', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->addColumn('status', function ($item) {
                $badge = $item->status == Status::ACTIVE ? 'bg-label-success' : 'bg-label-secondary';
                return '<span class="badge ' . $badge . '">' . ucfirst($item->status) . '</span>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='editLossReason' href='javascript:void(0)'
                      data-id='" . $item->loss_reason_id . "'><i class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteLossReason'
                    data-id='{$item->loss_reason_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['business', 'status', 'action'])
            ->make(true);
    }

    public function getById($loss_reason_id)
    {
        return $this->model_loss_reason->getModel()::with($this->with)->find($loss_reason_id);
    }

    public function save($obj)
    {
        $business_id = $obj['business_id'] ?? Auth::user()->business_id;

        $data = [
            'business_id' => $business_id,
            'name'        => $obj['name'],
            'status'      => $obj['status'] ?? Status::ACTIVE,
        ];

        if (!empty($obj['loss_reason_id'])) {
            $data['updatedby_id'] = Auth::id();
            $data['date_updated'] = now();

            $this->model_loss_reason->update($data, $obj['loss_reason_id']);
            return $this->model_loss_reason->find($obj['loss_reason_id']);
        }

        $data['loss_reason_id'] = generateUuid();
        $data['is_deleted'] = 0;
        $data['createdby_id'] = Auth::id();
        $data['date_created'] = now();

        return $this->model_loss_reason->create($data);
    }

    public function delete($loss_reason_id)
    {
        return $this->model_loss_reason->update([
            'is_deleted'   => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $loss_reason_id);
    }

    public function getActiveByBusiness($business_id)
    {
        return $this->model_loss_reason->getModel()::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->orderBy('name')
            ->get();
    }
}
