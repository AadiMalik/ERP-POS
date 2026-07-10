<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDetail;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class PurchaseRequestService
{
    protected $model_purchase_request;
    protected $model_purchase_request_details;
    protected $with = [
        'business',
        'branch',
        'supplier',
        'warehouse',
        'purchaseRequestDetails',
        'purchaseRequestDetails.product',
        'purchaseRequestDetails.product.productVariations',
        'purchaseRequestDetails.productVariation',
        'purchaseRequestDetails.unit',
    ];

    public function __construct()
    {
        $this->model_purchase_request = new Repository(new PurchaseRequest());
        $this->model_purchase_request_details = new Repository(new PurchaseRequestDetail());
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
            $wh[] = ['purchase_request_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }

        if (!empty($obj['end_date'])) {
            $wh[] = ['purchase_request_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];
        $datatable = $this->model_purchase_request->getModel()::with($this->with)
            ->withCount('purchaseRequestDetails as total_products')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('purchase_request_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('purchase_request_date', function ($item) {
                return !empty($item->purchase_request_date)
                    ? localDate($item->purchase_request_date)
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
                return decimal($item->total_products ?? 0);
            })
            ->addColumn('total', function ($item) {
                return currency($item->total ?? 0);
            })
            ->addColumn('status', function ($item) {

                $statuses = [
                    Status::PENDING   => ucfirst(Status::PENDING),
                    Status::APPROVED  => ucfirst(Status::APPROVED),
                    Status::QUOTATION_SENT => ucfirst(Status::QUOTATION_SENT),
                    Status::QUOTATION_RECEIVED => ucfirst(Status::QUOTATION_RECEIVED),
                    Status::CONVERTED => ucfirst(Status::CONVERTED),
                    Status::CANCELLED => ucfirst(Status::CANCELLED),
                ];

                $html = "<select class='form-select form-select-sm change-status'
                data-id='{$item->purchase_request_id}'>";

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
                     href='" . route('purchase-request.edit', $item->purchase_request_id) . "'
                    id='editPurchaseRequest'>

                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deletePurchaseRequest'
                    data-id='{$item->purchase_request_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['purchase_request_date', 'business', 'branch', 'warehouse', 'supplier', 'total_products', 'total',  'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {

            //====================================
            // Update
            //====================================

            if (!empty($obj['purchase_request_id'])) {

                $purchase_request = $this->model_purchase_request
                    ->getModel()::findOrFail($obj['purchase_request_id']);

                $purchase_request->update([
                    'business_id'            => $obj['business_id'],
                    'supplier_id'            => $obj['supplier_id'],
                    'warehouse_id'           => $obj['warehouse_id'],
                    'purchase_request_date'    => $obj['purchase_request_date'],
                    'purchase_expected_date' => $obj['purchase_expected_date'],
                    'description'            => $obj['description'],
                    'updatedby_id'           => Auth::user()->id,
                    'date_updated'           => now(),
                ]);

                // Remove previous items

                $this->model_purchase_request_details->getModel()::where('purchase_request_id', $purchase_request->purchase_request_id)
                    ->delete();
            }

            //====================================
            // Create
            //====================================

            else {

                $purchase_request = $this->model_purchase_request->create([
                    'purchase_request_id'      => generateUuid(),
                    'business_id'            => $obj['business_id'],
                    'supplier_id'            => $obj['supplier_id'],
                    'warehouse_id'           => $obj['warehouse_id'],
                    'purchase_request_no'      => $obj['purchase_request_no'],
                    'purchase_request_date'    => $obj['purchase_request_date'],
                    'purchase_expected_date' => $obj['purchase_expected_date'],
                    'description'            => $obj['description'],
                    'createdby_id'           => Auth::user()->id,
                    'date_created'           => now(),
                ]);
            }

            //====================================
            // Save Items
            //====================================

            foreach ($obj['products'] as $product) {

                $this->model_purchase_request_details->create([

                    'purchase_request_detail_id'              => generateUuid(),
                    'purchase_request_id'                     => $purchase_request->purchase_request_id,
                    'product_id'                            => $product['product_id'],
                    'product_variation_id'                  => $product['product_variation_id'],
                    'unit_id'                               => $product['unit_id'],
                    'requested_quantity'                    => $product['requested_quantity']
                ]);
            }

            DB::commit();

            return true;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($purchase_request_id)
    {
        return $this->model_purchase_request->with($this->with)->find($purchase_request_id);
    }

    public function status($obj)
    {
        return $this->model_purchase_request->update([
            'status' => $obj['status'],
            'updatedby_id' => Auth::user()->id,
            'date_updated' => now()
        ], $obj['purchase_request_id']);
    }

    public function delete($purchase_request_id)
    {
        return $this->model_purchase_request->update([
            'is_deleted' => 1,
            'status' => Status::CANCELLED,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $purchase_request_id);
    }

    public function getAll()
    {
        return $this->model_purchase_request->getModel()::where('is_deleted', 0)
            ->get();
    }
    public function getAllPending()
    {
        return $this->model_purchase_request->getModel()::where('status', Status::PENDING)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getAllApproved()
    {
        return $this->model_purchase_request->getModel()::where('status', Status::APPROVED)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getDetails($purchase_request_id)
    {
        try {
            $purchase_request = $this->model_purchase_request->getModel()::with($this->with)->findOrFail($purchase_request_id);

            $data = [
                'header' => [
                    'purchase_request_id' => $purchase_request->purchase_request_id,
                    'supplier_id' => $purchase_request->supplier_id,
                    'warehouse_id' => $purchase_request->warehouse_id,
                    'branch_id' => $purchase_request->branch_id,
                    'purchase_request_no' => $purchase_request->purchase_request_no,
                    'purchase_request_date' => $purchase_request->purchase_request_date,
                    'purchase_expected_date' => $purchase_request->purchase_expected_date,
                    'description' => $purchase_request->description,
                ],
                'details' => []
            ];

            foreach ($purchase_request->purchaseRequestDetails as $detail) {
                $productVariations = [];

                foreach ($detail->product->productVariations as $variation) {

                    $productVariations[] = [
                        'product_variation_id' => $variation->product_variation_id,
                        'name' => $variation->name,
                        'purchase_price' => $variation->purchase_price,
                        'unit_id' => optional($variation->purchase_unit)->unit_id,
                        'unit_name' => optional($variation->purchase_unit)->name,
                    ];
                }

                $data['details'][] = [
                    'product_id' => $detail->product_id,
                    'product_name' => $detail->product->name ?? '',
                    'product_variation_id' => $detail->product_variation_id,
                    'product_variation_name' => $detail->productVariation->name ?? '',
                    'requested_quantity' => $detail->requested_quantity,
                    'unit_id' => $detail->unit_id,
                    'unit_name' => $detail->unit->name ?? 'N/A',
                    'productVariations' => $productVariations
                ];
            }

            return $data;
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function getByBusiness($business_id)
    {
        return $this->model_purchase_request->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
