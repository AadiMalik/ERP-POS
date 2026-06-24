<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\ProductVariationBatch;
use App\Models\ProductVariationUnitConversion;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class ProductVariationBatchService
{
    protected $model_product_variation_batch;
    protected $with = ['business','product', 'productVariation', 'warehouse', 'createdBy', 'updatedBy', 'deletedBy'];

    public function __construct()
    {
        $this->model_product_variation_batch = new Repository(new ProductVariationBatch());
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
        if (isset($obj['product_id']) && $obj['product_id'] != 0 && $obj['product_id'] != "") {
            $wh[] = ['product_id', $obj['product_id']];
        }
        if (isset($obj['product_variation_id']) && $obj['product_variation_id'] != 0 && $obj['product_variation_id'] != "") {
            $wh[] = ['product_variation_id', $obj['product_variation_id']];
        }
        if (isset($obj['warehouse_id']) && $obj['warehouse_id'] != 0 && $obj['warehouse_id'] != "") {
            $wh[] = ['warehouse_id', $obj['warehouse_id']];
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
        $datatable = $this->model_product_variation_batch->getModel()::where($wh)
            ->with($this->with)
            ->where('is_deleted', 0)
            ->orderBy('date_created', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
        ->addColumn('product', function ($item) {

                return $item->product?->name ?? '-';
            })
            ->addColumn('business', function ($item) {

                return $item->business?->name ?? '-';
            })
            ->addColumn('productVariation', function ($item) {

                return $item->productVariation?->name ?? '-';
            })
            ->addColumn('warehouse', function ($item) {

                return $item->warehouse?->name ?? '-';
            })
            ->addColumn('status', function ($item) {

                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusProductVariationBatch"
                        type="checkbox"
                        data-id="' . $item->product_variation_batch_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='editProductVariationBatch' href='javascript:void(0)'
                      data-toggle='tooltip'  data-id='" . $item->product_variation_batch_id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteProductVariationBatch'
                    data-id='{$item->product_variation_batch_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['business','product', 'productVariation', 'warehouse', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {

        if (!empty($obj['product_variation_batch_id'])) {
            $obj['updatedby_id'] = Auth::user()->id;
            $obj['date_updated'] = now();
            $this->model_product_variation_batch->update($obj, $obj['product_variation_batch_id']);
            return $this->model_product_variation_batch->find($obj['product_variation_batch_id']);
        }

        $obj['product_variation_batch_id'] = generateUuid();
        $obj['createdby_id'] = Auth::user()->id;
        $obj['date_created'] = now();
        $saved_obj = $this->model_product_variation_batch->create($obj);
        return $saved_obj;
    }

    public function getById($product_variation_batch_id)
    {
        return $this->model_product_variation_batch->find($product_variation_batch_id);
    }
    public function status($product_variation_batch_id)
    {
        return $this->model_product_variation_batch->update([
            'status' => ($this->model_product_variation_batch->find($product_variation_batch_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $product_variation_batch_id);
    }

    public function delete($product_variation_batch_id)
    {
        return $this->model_product_variation_batch->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $product_variation_batch_id);
    }

    public function getAll()
    {
        return $this->model_product_variation_batch->getModel()::with($this->with)
            ->where('business_id', Auth::user()->business_id)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getAllActive()
    {
        return $this->model_product_variation_batch->getModel()::with($this->with)
            ->where('business_id', Auth::user()->business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getByProduct($product_id)
    {
        return $this->model_product_variation_batch->getModel()::with($this->with)
            ->where('product_id', $product_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getByProductVariation($product_variation_id)
    {
        return $this->model_product_variation_batch->getModel()::with($this->with)
            ->where('product_variation_id', $product_variation_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getByWarehouse($warehouse_id)
    {
        return $this->model_product_variation_batch->getModel()::with($this->with)
            ->where('warehouse_id', $warehouse_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getByBusiness($business_id)
    {
        return $this->model_product_variation_batch->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }
}
