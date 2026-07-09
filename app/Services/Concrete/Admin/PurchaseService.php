<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class PurchaseService
{
    protected $model_purchase;
    protected $model_purchase_details;
    protected $with = [
        'business',
        'branch',
        'supplier',
        'warehouse',
        'purchaseDetails',
        'purchaseDetails.product',
        'purchaseDetails.product.productVariations',
        'purchaseDetails.productVariation',
        'purchaseDetails.productVariation.productVariationUnitConversion',
        'purchaseDetails.unit',
        'purchaseDetails.productVariationUnitConversion',
    ];

    public function __construct()
    {
        $this->model_purchase = new Repository(new Purchase());
        $this->model_purchase_details = new Repository(new PurchaseDetail());
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
        if (isset($obj['purchase_id']) && $obj['purchase_id'] != 0 && $obj['purchase_id'] != "") {
            $wh[] = ['purchase_id', $obj['purchase_id']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['purchase_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }

        if (!empty($obj['end_date'])) {
            $wh[] = ['purchase_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];
        $datatable = $this->model_purchase->getModel()::with($this->with)
            ->withCount('purchaseDetails as total_products')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('purchase_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('purchase_date', function ($item) {
                return !empty($item->purchase_date)
                    ? localDate($item->purchase_date)
                    : 'N/A';
            })
            ->addColumn('purchase_order_no', function ($item) {
                return $item->purchase_order->purchase_order_no ?? '';
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
                return decimal($item->total_products ?? 0);
            })
            ->addColumn('total', function ($item) {
                return currency($item->total ?? 0);
            })
            ->addColumn('status', function ($item) {

                $statuses = [
                    Status::PENDING   => ucfirst(Status::PENDING),
                    Status::APPROVED  => ucfirst(Status::APPROVED),
                    Status::COMPLETED => ucfirst(Status::COMPLETED),
                    Status::CANCELLED => ucfirst(Status::CANCELLED),
                ];

                $html = "<select class='form-select form-select-sm change-status'
                data-id='{$item->purchase_id}'>";

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
                     href='" . route('purchase-order.edit', $item->purchase_id) . "'
                    id='editPurchase'>

                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deletePurchase'
                    data-id='{$item->purchase_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['purchase_date','purchase_order_no', 'business', 'branch', 'warehouse', 'supplier', 'total_products', 'total',  'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {

            //====================================
            // Update
            //====================================

            if (!empty($obj['purchase_id'])) {

                $purchase_order = $this->model_purchase
                    ->getModel()::findOrFail($obj['purchase_id']);

                $purchase_order->update([
                    'business_id'            => $obj['business_id'],
                    'supplier_id'            => $obj['supplier_id'],
                    'warehouse_id'           => $obj['warehouse_id'],
                    'purchase_date'          => $obj['purchase_date'],
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

                $this->model_purchase_details->getModel()::where('purchase_id', $purchase_order->purchase_id)
                    ->delete();
            }

            //====================================
            // Create
            //====================================

            else {

                $purchase_order = $this->model_purchase->create([
                    'purchase_id'      => generateUuid(),
                    'business_id'            => $obj['business_id'],
                    'supplier_id'            => $obj['supplier_id'],
                    'warehouse_id'           => $obj['warehouse_id'],
                    'purchase_order_no'      => $obj['purchase_order_no'],
                    'purchase_date'    => $obj['purchase_date'],
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

                $this->model_purchase_details->create([

                    'purchase_order_detail_id'              => generateUuid(),
                    'purchase_id'                     => $purchase_order->purchase_id,

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

    public function getById($purchase_id)
    {
        return $this->model_purchase->with($this->with)->find($purchase_id);
    }

    public function status($obj)
    {
        return $this->model_purchase->update([
            'status' => $obj['status'],
            'updatedby_id' => Auth::user()->id,
            'date_updated' => now()
        ], $obj['purchase_id']);
    }

    public function delete($purchase_id)
    {
        return $this->model_purchase->update([
            'is_deleted' => 1,
            'status' => Status::CANCELLED,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $purchase_id);
    }

    public function getAll()
    {
        return $this->model_purchase->getModel()::where('is_deleted', 0)
            ->get();
    }
    public function getDetails($purchase_id)
    {
        try {
            $purchase_order = $this->model_purchase->getModel()::with($this->with)->findOrFail($purchase_id);

            $data = [
                'header' => [
                    'purchase_id' => $purchase_order->purchase_id,
                    'supplier_id' => $purchase_order->supplier_id,
                    'warehouse_id' => $purchase_order->warehouse_id,
                    'branch_id' => $purchase_order->branch_id,
                    'purchase_order_no' => $purchase_order->purchase_order_no,
                    'purchase_date' => $purchase_order->purchase_date,
                    'purchase_expected_date' => $purchase_order->purchase_expected_date,
                    'subtotal' => $purchase_order->subtotal,
                    'discount' => $purchase_order->discount,
                    'tax' => $purchase_order->tax,
                    'shipping_charge' => $purchase_order->shipping_charge,
                    'description' => $purchase_order->description,
                ],
                'details' => []
            ];

            foreach ($purchase_order->purchaseDetails as $detail) {
                $conversions = [];
                if ($detail->productVariation) {
                    foreach ($detail->productVariation->productVariationUnitConversion as $conversion) {
                        $conversions[] = [
                            'product_variation_unit_conversion_id' => $conversion->product_variation_unit_conversion_id,
                            'from_unit_id' => $conversion->from_unit_id,
                            'from_unit_name' => $conversion->fromUnit->name ?? 'N/A',
                            'to_unit_id' => $conversion->to_unit_id,
                            'to_unit_name' => $conversion->toUnit->name ?? 'N/A',
                            'conversion_factor' => $conversion->conversion_factor,
                        ];
                    }
                }

                $data['details'][] = [
                    'product_id' => $detail->product_id,
                    'product_name' => $detail->product->name ?? '',
                    'product_variation_id' => $detail->product_variation_id,
                    'product_variation_name' => $detail->productVariation->name ?? '',
                    'product_variation_unit_conversion_id' => $detail->product_variation_unit_conversion_id,
                    'base_quantity' => $detail->base_quantity,
                    'ordered_quantity' => $detail->ordered_quantity,
                    'received_quantity' => $detail->received_quantity,
                    'rejected_quantity' => $detail->rejected_quantity,
                    'unit_id' => $detail->unit_id,
                    'unit_name' => $detail->unit->name ?? 'N/A',
                    'conversion_factor' => $detail->conversion_factor,
                    'unit_price' => $detail->unit_price,
                    'total' => $detail->total,
                    'conversions' => $conversions,
                ];
            }

            return $data;
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function getByBusiness($business_id)
    {
        return $this->model_purchase->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
