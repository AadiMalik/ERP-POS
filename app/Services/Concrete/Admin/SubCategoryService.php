<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\SubCategory;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class SubCategoryService
{
      protected $model_sub_category;
      protected $with = ['category', 'business'];

      public function __construct()
      {
            $this->model_sub_category = new Repository(new SubCategory());
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
            if (isset($obj['sub_category_id']) && $obj['sub_category_id'] != 0 && $obj['sub_category_id'] != "") {
                  $wh[] = ['sub_category_id', $obj['sub_category_id']];
            }
            if (!empty($obj['start_date'])) {
                  $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
            }

            if (!empty($obj['end_date'])) {
                  $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
            }
            
            $allow_roles = [
                  RoleNames::SUPERADMIN,
                  RoleNames::BUSINESSADMIN
            ];
            $datatable = $this->model_sub_category->getModel()::where($wh)
                  ->with($this->with)
                  ->where('is_deleted', 0)
                  ->orderBy('name', $orderBy);
            $datatable = applyRoleScope($datatable, $allow_roles);
            return DataTables::of($datatable)
                  ->addColumn('category', function ($item) {

                        return $item->category?->name ?? '-';
                  })
                  ->addColumn('business', function ($item) {

                        return $item->business?->name ?? '-';
                  })
                  ->addColumn('status', function ($item) {

                        $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                        return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusSubCategory"
                        type="checkbox"
                        data-id="' . $item->sub_category_id . '"
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
                     id='editSubCategory' href='javascript:void(0)'
                      data-toggle='tooltip'  data-id='" . $item->sub_category_id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteSubCategory'
                    data-id='{$item->sub_category_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
                  })
                  ->rawColumns(['category','business', 'status', 'logo', 'action'])
                  ->make(true);
      }

      public function save($obj)
      {

            if (!empty($obj['sub_category_id'])) {
                  $obj['updatedby_id'] = Auth::user()->id;
                  $obj['date_updated'] = now();
                  $this->model_sub_category->update($obj, $obj['sub_category_id']);
                  return $this->model_sub_category->find($obj['sub_category_id']);
            }
            //check limit
            $limit = checkPackageLimit('categories');

            if (!$limit['status']) {
                  throw new Exception($limit['message']);
            }

            $obj['sub_category_id'] = generateUuid();
            $obj['createdby_id'] = Auth::user()->id;
            $obj['date_created'] = now();
            $saved_obj = $this->model_sub_category->create($obj);
            return $saved_obj;
      }

      public function getById($sub_category_id)
      {
            return $this->model_sub_category->find($sub_category_id);
      }
      public function status($sub_category_id)
      {
            return $this->model_sub_category->update([
                  'status' => ($this->model_sub_category->find($sub_category_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
                  'updatedby_id' => Auth::id(),
                  'date_updated' => now()
            ], $sub_category_id);
      }

      public function delete($sub_category_id)
      {
            return $this->model_sub_category->update([
                  'is_deleted' => 1,
                  'deletedby_id' => Auth::id(),
                  'date_deleted' => now()
            ], $sub_category_id);
      }

      public function getAll()
      {
            return $this->model_sub_category->getModel()::with($this->with)
                  ->where('business_id', Auth::user()->business_id)
                  ->where('is_deleted', 0)
                  ->get();
      }
      public function getAllActive()
      {
            return $this->model_sub_category->getModel()::with($this->with)
                  ->where('business_id', Auth::user()->business_id)
                  ->where('status', Status::ACTIVE)
                  ->where('is_deleted', 0)
                  ->get();
      }

      public function getByCategory($category_id)
      {
            return $this->model_sub_category->getModel()::with($this->with)
                  ->where('category_id', $category_id)
                  ->where('is_deleted', 0)
                  ->get();
      }

      public function getByBusiness($business_id)
      {
            return $this->model_sub_category->getModel()::with($this->with)
                  ->where('business_id', $business_id)
                  ->where('is_deleted', 0)
                  ->get();
      }
}
