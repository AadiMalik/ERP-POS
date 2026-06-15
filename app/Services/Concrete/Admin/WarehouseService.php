<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Models\Warehouse;
use App\Repository\Repository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class WarehouseService
{
      protected $model_warehouse;

      public function __construct()
      {
            $this->model_warehouse = new Repository(new Warehouse());
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
            if (isset($obj['branch_id']) && $obj['branch_id'] != 0 && $obj['branch_id'] != "") {
                  $wh[] = ['branch_id', $obj['branch_id']];
            }
            if (!empty($obj['start_date'])) {
                  $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
            }

            if (!empty($obj['end_date'])) {
                  $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
            }
            $with = ['business', 'branch'];
            $allow_roles = [
                  RoleNames::SUPERADMIN,
                  RoleNames::BUSINESSADMIN
            ];
            $datatable = $this->model_warehouse->getModel()::where($wh)
                  ->with($with)
                  ->orderBy('name', $orderBy);
            $datatable = applyRoleScope($datatable, $allow_roles);
            return DataTables::of($datatable)
                  ->addColumn('status', function ($item) {

                        $checked = $item->status == 'active' ? 'checked' : '';

                        return '
                      <div class="form-check form-switch mb-0">
                          <input
                              class="form-check-input statusWarehouse"
                              type="checkbox"
                              data-id="' . $item->warehouse_id . '"
                              ' . $checked . '>
                      </div>
                  ';
                  })
                  ->addColumn('business', function ($item) {
                        return $item->business?->name ?? '-';
                  })

                  ->addColumn('branch', function ($item) {
                        return $item->branch?->name ?? '-';
                  })
                  ->addColumn('action', function ($item) {

                        return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('warehouse.edit', $item->warehouse_id) . "'
                    id='editWarehouse'>

                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteWarehouse'
                    data-id='{$item->warehouse_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
                  })
                  ->rawColumns(['status', 'business', 'branch', 'action'])
                  ->make(true);
      }

      public function save($obj)
      {

            if (!empty($obj['warehouse_id'])) {
                  $obj['updatedby_id'] = Auth::user()->id;
                  $obj['date_updated'] = now();
                  $this->model_warehouse->update($obj, $obj['warehouse_id']);
                  return $this->model_warehouse->find($obj['warehouse_id']);
            }
            //check limit
            $limit = checkPackageLimit('warehouses');

            if (!$limit['status']) {
                  throw new Exception($limit['message']);
            }

            $obj['warehouse_id'] = generateUuid();
            $obj['createdby_id'] = Auth::user()->id;
            $obj['date_created'] = now();
            $saved_obj = $this->model_warehouse->create($obj);
            return $saved_obj;
      }

      public function getById($warehouse_id)
      {
            return $this->model_warehouse->find($warehouse_id);
      }
      public function status($warehouse_id)
      {
            return $this->model_warehouse->update([
                  'status' => ($this->model_warehouse->find($warehouse_id)->status == 'active' ? 'inactive' : 'active'),
                  'updatedby_id' => Auth::id(),
                  'date_updated' => now()
            ], $warehouse_id);
      }

      public function delete($warehouse_id)
      {
            return $this->model_warehouse->update([
                  'is_deleted' => 1,
                  'deletedby_id' => Auth::id(),
                  'date_deleted' => now()
            ], $warehouse_id);
      }

      public function getAll()
      {
            return $this->model_warehouse->getModel()::with(['business', 'branch'])
                  ->where('business_id', Auth::user()->business_id)
                  ->get();
      }

      public function getByBusiness($business_id)
      {
            return $this->model_warehouse->getModel()::with(['business', 'branch'])
                  ->where('business_id', $business_id)
                  ->where('is_deleted', 0)
                  ->get();
      }

      public function getByBranch($branch_id)
      {
            return $this->model_warehouse->getModel()::with(['business', 'branch'])
                  ->where('branch_id', $branch_id)
                  ->where('is_deleted', 0)
                  ->get();
      }
}
