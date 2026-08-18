<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Enums\Filter;
use App\Enums\Status;
use App\Models\Shift;
use App\Repository\Repository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class ShiftService
{
    protected $model_shift;

    public function __construct()
    {
        $this->model_shift = new Repository(new Shift());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $datatable = $this->model_shift->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('timing', function ($item) {
                return date('h:i A', strtotime($item->start_time)) . ' - ' . date('h:i A', strtotime($item->end_time));
            })
            ->addColumn('working_days', function ($item) {
                return $item->working_days ? implode(', ', array_map('ucfirst', $item->working_days)) : '-';
            })
            ->addColumn('status', function ($item) {
                return $item->status == Status::ACTIVE
                    ? '<span class="badge bg-label-success">Active</span>'
                    : '<span class="badge bg-label-secondary">Inactive</span>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('shift.edit', $item->shift_id) . "'>
                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteShift'
                    data-id='{$item->shift_id}'>
                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['timing', 'working_days', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        if (!empty($obj['shift_id'])) {
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $this->model_shift->update($obj, $obj['shift_id']);
            return $this->model_shift->find($obj['shift_id']);
        }

        $obj['shift_id'] = generateUuid();
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();
        return $this->model_shift->create($obj);
    }

    public function getById($shift_id)
    {
        return $this->model_shift->find($shift_id);
    }

    public function delete($shift_id)
    {
        return $this->model_shift->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $shift_id);
    }

    public function getAllActive()
    {
        $query = $this->model_shift->getModel()::where('is_deleted', 0)
            ->where('status', Status::ACTIVE);
        return applyRoleScope($query)->orderBy('name')->get();
    }
}
