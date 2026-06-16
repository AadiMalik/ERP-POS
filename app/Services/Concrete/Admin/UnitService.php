<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Unit;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class UnitService
{
      protected $model_unit;

      public function __construct()
      {
            $this->model_unit = new Repository(new Unit());
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
            $allow_roles = [
                  RoleNames::SUPERADMIN
            ];
            $datatable = $this->model_unit->getModel()::where($wh)
                  ->where('is_deleted', 0)
                  ->orderBy('name', $orderBy);
            $datatable = applyRoleScope($datatable, $allow_roles);
            return DataTables::of($datatable)
                  ->addColumn('status', function ($item) {

                        $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                        return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusUnit"
                        type="checkbox"
                        data-id="' . $item->unit_id . '"
                        ' . $checked . '>
                </div>
            ';
                  })
                  ->addColumn('action', function ($item) {

                        return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='editUnit' href='javascript:void(0)'
                      data-toggle='tooltip'  data-id='" . $item->unit_id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteUnit'
                    data-id='{$item->unit_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
                  })
                  ->rawColumns(['status',  'action'])
                  ->make(true);
      }

      public function save($obj)
      {

            if (!empty($obj['unit_id'])) {
                  $obj['updatedby_id'] = Auth::user()->id;
                  $obj['date_updated'] = now();
                  $this->model_unit->update($obj, $obj['unit_id']);
                  return $this->model_unit->find($obj['unit_id']);
            }

            $obj['unit_id'] = generateUuid();
            $obj['createdby_id'] = Auth::user()->id;
            $obj['date_created'] = now();
            $saved_obj = $this->model_unit->create($obj);
            return $saved_obj;
      }

      public function getById($unit_id)
      {
            return $this->model_unit->find($unit_id);
      }
      public function status($unit_id)
      {
            return $this->model_unit->update([
                  'status' => ($this->model_unit->find($unit_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
                  'updatedby_id' => Auth::id(),
                  'date_updated' => now()
            ], $unit_id);
      }

      public function delete($unit_id)
      {
            return $this->model_unit->update([
                  'is_deleted' => 1,
                  'deletedby_id' => Auth::id(),
                  'date_deleted' => now()
            ], $unit_id);
      }

      public function getAll()
      {
            return $this->model_unit->getModel()::where('is_deleted', 0)
                  ->get();
      }
      public function getAllActive()
      {
            return $this->model_unit->getModel()::where('status', Status::ACTIVE)
                  ->where('is_deleted', 0)
                  ->get();
      }
}
