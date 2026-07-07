<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class PurchaseOrderService
{
    protected $model_purchase_order;
    protected $model_purchase_order_details;
    protected $with = [
        'business',
        'branch',
        'supplier',
        'warehouse',
        'purchaseOrderDetails',
        'purchaseOrderDetails.product',
        'purchaseOrderDetails.product.productVariations',
        'purchaseOrderDetails.productVariation',
        'purchaseOrderDetails.productVariation.productVariationUnitConversion',
        'purchaseOrderDetails.unit',
        'purchaseOrderDetails.productVariationUnitConversion',
    ];

    public function __construct()
    {
        $this->model_purchase_order = new Repository(new PurchaseOrder());
        $this->model_purchase_order_details = new Repository(new PurchaseOrderDetail());
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
        if (isset($obj['supplier_id']) && $obj['supplier_id'] != 0 && $obj['supplier_id'] != "") {
            $wh[] = ['supplier_id', $obj['supplier_id']];
        }
        if (isset($obj['warehouse_id']) && $obj['warehouse_id'] != 0 && $obj['warehouse_id'] != "") {
            $wh[] = ['warehouse_id', $obj['warehouse_id']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['purchase_order_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }

        if (!empty($obj['end_date'])) {
            $wh[] = ['purchase_order_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];
        $datatable = $this->model_purchase_order->getModel()::with($this->with)
            ->withCount('purchaseOrderDetails as total_products')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('purchase_order_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('purchase_order_date', function ($item) {
                return !empty($item->purchase_order_date)
                    ? Carbon::parse($item->purchase_order_date)->format('d-m-Y')
                    : 'N/A';
            })
            ->addColumn('supplier', function ($item) {
                return $item->supplier->code ?? '' . ' ' . $item->supplier->name ?? '';
            })
            ->addColumn('warehouse', function ($item) {
                return $item->warehouse->name ?? '';
            })
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->addColumn('branch', function ($item) {
                return $item->branch->name ?? '';
            })
            ->addColumn('total_products', function ($item) {
                return number_format($item->total_products ?? 0, 3);
            })
            ->addColumn('status', function ($item) {

                $statuses = [
                    Status::PENDING   => ucfirst(Status::PENDING),
                    Status::APPROVED  => ucfirst(Status::APPROVED),
                    Status::COMPLETED => ucfirst(Status::COMPLETED),
                    Status::CANCELLED => ucfirst(Status::CANCELLED),
                ];

                $html = "<select class='form-select form-select-sm change-status'
                data-id='{$item->purchase_order_id}'>";

                foreach ($statuses as $value => $label) {
                    $selected = $item->status == $value ? 'selected' : '';
                    $html .= "<option value='{$value}' {$selected}>{$label}</option>";
                }

                $html .= "</select>";

                return $html;
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('purchase-order.edit', $item->purchase_order_id) . "'
                    id='editPurchaseOrder'>

                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deletePurchaseOrder'
                    data-id='{$item->purchase_order_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['purchase_order_date', 'business', 'branch', 'warehouse', 'supplier', 'total_products',  'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {

            //====================================
            // Update
            //====================================

            if (!empty($obj['purchase_order_id'])) {

                $purchase_order = $this->model_purchase_order
                    ->getModel()::findOrFail($obj['purchase_order_id']);

                $purchase_order->update([
                    'business_id'            => $obj['business_id'],
                    'supplier_id'            => $obj['supplier_id'],
                    'warehouse_id'           => $obj['warehouse_id'],
                    'purchase_order_date'    => $obj['purchase_order_date'],
                    'purchase_expected_date' => $obj['purchase_expected_date'],
                    'description'            => $obj['description'],
                    'subtotal'               => $obj['subtotal'],
                    'discount'               => $obj['discount'],
                    'discount_amount'        => $obj['discount_amount'],
                    'tax'                    => $obj['tax'],
                    'tax_amount'             => $obj['tax_amount'],
                    'shipping_charge'        => $obj['shipping_charge'],
                    'total'                  => $obj['total'],
                    'updatedby_id'           => Auth::user()->id,
                    'date_updated'           => now(),
                ]);

                // Remove previous items

                $this->model_purchase_order_details->getModel()::where('purchase_order_id', $purchase_order->purchase_order_id)
                    ->delete();
            }

            //====================================
            // Create
            //====================================

            else {

                $purchase_order = $this->model_purchase_order->create([
                    'purchase_order_id'      => generateUuid(),
                    'business_id'            => $obj['business_id'],
                    'supplier_id'            => $obj['supplier_id'],
                    'warehouse_id'           => $obj['warehouse_id'],
                    'purchase_order_no'      => $obj['purchase_order_no'],
                    'purchase_order_date'    => $obj['purchase_order_date'],
                    'purchase_expected_date' => $obj['purchase_expected_date'],
                    'description'            => $obj['description'],
                    'subtotal'               => $obj['subtotal'],
                    'discount'               => $obj['discount'],
                    'discount_amount'        => $obj['discount_amount'],
                    'tax'                    => $obj['tax'],
                    'tax_amount'             => $obj['tax_amount'],
                    'shipping_charge'        => $obj['shipping_charge'],
                    'total'                  => $obj['total'],
                    'createdby_id'           => Auth::user()->id,
                    'date_created'           => now(),
                ]);
            }

            //====================================
            // Save Items
            //====================================

            foreach ($obj['products'] as $product) {

                $this->model_purchase_order_details->create([

                    'purchase_order_detail_id'              => generateUuid(),
                    'purchase_order_id'                     => $purchase_order->purchase_order_id,

                    'product_id'                            => $product['product_id'],
                    'product_variation_id'                  => $product['product_variation_id'],
                    'product_variation_unit_conversion_id'  => $product['product_variation_unit_conversion_id'],

                    'unit_id'                               => $product['unit_id'],
                    'base_quantity'                         => $product['ordered_quantity'],
                    'ordered_quantity'                      => $product['ordered_quantity'],
                    'conversion_factor'                     => $product['conversion_factor'],
                    'unit_price'                            => $product['unit_price'],
                    'total'                                 => $product['total'],
                ]);
            }

            DB::commit();

            return true;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($purchase_order_id)
    {
        return $this->model_purchase_order->with($this->with)->find($purchase_order_id);
    }

    public function status($obj)
    {
        return $this->model_purchase_order->update([
            'status' => $obj['status'],
            'updatedby_id' => Auth::user()->id,
            'date_updated' => now()
        ], $obj['purchase_order_id']);
    }

    public function delete($purchase_order_id)
    {
        return $this->model_purchase_order->update([
            'is_deleted' => 1,
            'status' => Status::CANCELLED,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $purchase_order_id);
    }

    public function getAll()
    {
        return $this->model_purchase_order->getModel()::where('is_deleted', 0)
            ->get();
    }
}
