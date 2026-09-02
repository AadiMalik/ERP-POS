<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\FixedAssetCategory;
use App\Repository\Repository;
use App\Traits\Auditable;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class FixedAssetCategoryService
{
    use Auditable;

    protected $model;
    protected $with = ['business', 'createdby'];

    public function __construct()
    {
        $this->model = new Repository(new FixedAssetCategory());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (!empty($obj['business_id'])) {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (!empty($obj['status'])) {
            $wh[] = ['status', $obj['status']];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::FINANCEMANAGER,
            RoleNames::ACCOUNTANT,
            RoleNames::BRANCHADMIN,
        ];

        $datatable = $this->model->getModel()::with($this->with)
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('business', fn ($item) => $item->business->name ?? '')
            ->addColumn('status', function ($item) {
                $checked = $item->status === Status::ACTIVE ? 'checked' : '';
                return "<div class='form-check form-switch'>
                    <input class='form-check-input change-status' type='checkbox' data-id='{$item->fixed_asset_category_id}' {$checked}>
                </div>";
            })
            ->addColumn('action', function ($item) {
                $id = $item->fixed_asset_category_id;
                $edit = '';
                $delete = '';
                if (auth()->user()->can('fixed-asset-category.edit')) {
                    $edit = "<a href='" . url('admin/fixed-asset-category/edit/' . $id) . "' class='btn btn-sm btn-icon btn-primary'><i class='bx bx-edit'></i></a>";
                }
                if (auth()->user()->can('fixed-asset-category.delete')) {
                    $delete = "<button type='button' class='btn btn-sm btn-icon btn-danger delete' data-id='{$id}'><i class='bx bx-trash'></i></button>";
                }
                return "<div class='d-flex gap-1'>{$edit}{$delete}</div>";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function getAllActive($business_id = null)
    {
        $q = $this->model->getModel()::where('is_deleted', 0)->where('status', Status::ACTIVE);
        if ($business_id) {
            $q->where('business_id', $business_id);
        }
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::FINANCEMANAGER,
            RoleNames::ACCOUNTANT,
            RoleNames::BRANCHADMIN,
        ];
        $q = applyRoleScope($q, $allow_roles);
        return $q->orderBy('name')->get();
    }

    public function getById($id)
    {
        return $this->model->getModel()::with($this->with)
            ->where('fixed_asset_category_id', $id)
            ->where('is_deleted', 0)
            ->first();
    }

    public function save(array $obj)
    {
        DB::beginTransaction();
        try {
            $isUpdate = !empty($obj['fixed_asset_category_id']);
            if ($isUpdate) {
                $category = $this->getById($obj['fixed_asset_category_id']);
                if (!$category) {
                    throw new Exception('Category not found.');
                }
                $old = $category->toArray();
                $obj['updatedby_id'] = Auth::id();
                $obj['date_updated'] = now();
                $category->update($obj);
                $this->logActivity('fixed-asset-category', $category->fixed_asset_category_id, 'update', $old, $category->fresh()->toArray());
            } else {
                $obj['fixed_asset_category_id'] = generateUuid();
                $obj['createdby_id'] = Auth::id();
                $obj['date_created'] = now();
                $obj['status'] = $obj['status'] ?? Status::ACTIVE;
                $category = $this->model->getModel()::create($obj);
                $this->logActivity('fixed-asset-category', $category->fixed_asset_category_id, 'create', null, $category->toArray());
            }
            DB::commit();
            return $category;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function changeStatus($id)
    {
        $category = $this->getById($id);
        if (!$category) {
            throw new Exception('Category not found.');
        }
        $old = $category->status;
        $category->status = $category->status === Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE;
        $category->updatedby_id = Auth::id();
        $category->date_updated = now();
        $category->save();
        $this->logActivity('fixed-asset-category', $id, 'status', ['status' => $old], ['status' => $category->status]);
        return $category;
    }

    public function delete($id)
    {
        $category = $this->getById($id);
        if (!$category) {
            throw new Exception('Category not found.');
        }
        if ($category->fixedAssets()->where('is_deleted', 0)->exists()) {
            throw new Exception('Cannot delete a category that has fixed assets.');
        }
        $category->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ]);
        $this->logActivity('fixed-asset-category', $id, 'delete', $category->toArray(), null);
        return true;
    }
}
