<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Brand;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class BrandService
{
      protected $model_brand;

      public function __construct()
      {
            $this->model_brand = new Repository(new Brand());
      }

      /**
       * Public storefront listing - active brands for a business. Mirrors
       * CategoryService::getActivePublicByBusiness(). Consumed by the Vue
       * frontend instead of hard-coded brand data.
       */
      public function getActivePublicByBusiness($business_id)
      {
            $brands = $this->model_brand->getModel()::where('business_id', $business_id)
                  ->where('status', Status::ACTIVE)
                  ->where('is_deleted', 0)
                  ->orderBy('name')
                  ->get();

            return $brands->map(function ($brand) {
                  return [
                        'id' => $brand->brand_id,
                        'name' => $brand->name,
                        'slug' => Str::slug($brand->name),
                        'image' => $brand->logo_url,
                  ];
            })->values();
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
            $with = ['business'];
            $allow_roles = [
                  RoleNames::SUPERADMIN,
                  RoleNames::BUSINESSADMIN
            ];
            $datatable = $this->model_brand->getModel()::where($wh)
                  ->with($with)
                  ->where('is_deleted', 0)
                  ->orderBy('name', $orderBy);
            $datatable = applyRoleScope($datatable, $allow_roles);
            return DataTables::of($datatable)
                  ->addColumn('business', function ($item) {

                        return $item->business?->name ?? '-';
                  })
                  ->addColumn('status', function ($item) {

                        $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                        return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusBrand"
                        type="checkbox"
                        data-id="' . $item->brand_id . '"
                        ' . $checked . '>
                </div>
            ';
                  })
                  ->addColumn('logo', function ($item) {

                        if (!$item->logo_url) {
                              return '-';
                        }

                        return '
                      <a href="' . $item->logo_url . '" target="_blank">
                          <img
                              src="' . $item->logo_url . '"
                              alt="' . e($item->name) . '"
                              style="
                                  width:50px;
                                  height:50px;
                                  object-fit:contain;
                                  border:1px solid #ddd;
                                  border-radius:5px;
                                  padding:2px;
                                  background:#fff;
                              ">
                      </a>
                  ';
                  })
                  ->addColumn('action', function ($item) {

                        return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='editBrand' href='javascript:void(0)'
                      data-toggle='tooltip'  data-id='" . $item->brand_id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteBrand'
                    data-id='{$item->brand_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
                  })
                  ->rawColumns(['business', 'status', 'logo', 'action'])
                  ->make(true);
      }

      public function save($obj)
      {

            if (!empty($obj['brand_id'])) {
                  $obj['updatedby_id'] = Auth::user()->id;
                  $obj['date_updated'] = now();
                  $this->model_brand->update($obj, $obj['brand_id']);
                  return $this->model_brand->find($obj['brand_id']);
            }

            $obj['brand_id'] = generateUuid();
            $obj['createdby_id'] = Auth::user()->id;
            $obj['date_created'] = now();
            $saved_obj = $this->model_brand->create($obj);
            return $saved_obj;
      }

      public function getById($brand_id)
      {
            return $this->model_brand->find($brand_id);
      }
      public function status($brand_id)
      {
            return $this->model_brand->update([
                  'status' => ($this->model_brand->find($brand_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
                  'updatedby_id' => Auth::id(),
                  'date_updated' => now()
            ], $brand_id);
      }

      public function delete($brand_id)
      {
            return $this->model_brand->update([
                  'is_deleted' => 1,
                  'deletedby_id' => Auth::id(),
                  'date_deleted' => now()
            ], $brand_id);
      }

      public function getAll()
      {
            return $this->model_brand->getModel()::with('business')
                  ->where('business_id', Auth::user()->business_id)
                  ->where('is_deleted', 0)
                  ->get();
      }
      public function getAllActive()
      {
            return $this->model_brand->getModel()::with('business')
                  ->where('business_id', Auth::user()->business_id)
                  ->where('status', Status::ACTIVE)
                  ->where('is_deleted', 0)
                  ->get();
      }

      public function getByBusiness($business_id)
      {
            return $this->model_brand->getModel()::with('business')
                  ->where('business_id', $business_id)
                  ->where('is_deleted', 0)
                  ->get();
      }
}
