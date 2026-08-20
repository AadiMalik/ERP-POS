<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\JournalSourceTypes;
use App\Enums\ReferenceType;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Models\AccountingSetting;
use App\Models\GoodReceiptNote;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\ProductVariationStock;
use App\Models\ProductVariationStockTransaction;
use App\Services\Concrete\Admin\ProductVariationStockService;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class PurchaseReturnService
{
    use Auditable;

    protected $model_purchase_return;
    protected $model_purchase_return_details;
    protected $with = [
        'business',
        'branch',
        'supplier',
        'warehouse',
        'purchase',
        'goodReceiptNote',
        'purchaseReturnDetails',
        'purchaseReturnDetails.product',
        'purchaseReturnDetails.productVariation',
        'purchaseReturnDetails.productVariationUnitConversion',
        'purchaseReturnDetails.unit',
    ];

    public function __construct()
    {
        $this->model_purchase_return = new Repository(new PurchaseReturn());
        $this->model_purchase_return_details = new Repository(new PurchaseReturnDetail());
    }

    /**
     * Direct purchases eligible as a Purchase Return source: posted (approved)
     * direct purchases only - purchase_type = 'purchase_request' purchases never
     * post stock/JV themselves (their GRNs do), so they are never returnable
     * directly.
     */
    public function getEligibleDirectPurchases($business_id = null)
    {
        $query = Purchase::with(['supplier', 'warehouse'])
            ->where('purchase_type', 'direct')
            ->where('status', Status::APPROVED)
            ->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        } else {
            $query->where('business_id', Auth::user()->business_id);
        }

        return $query->get();
    }

    /**
     * GRNs eligible as a Purchase Return source: posted (approved) GRNs only,
     * since only an approved GRN has posted received stock to return against.
     */
    public function getEligibleGrns($business_id = null)
    {
        $query = GoodReceiptNote::with(['supplier', 'warehouse', 'purchase'])
            ->where('status', Status::APPROVED)
            ->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        } else {
            $query->where('business_id', Auth::user()->business_id);
        }

        return $query->get();
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
        if (isset($obj['return_type']) && $obj['return_type'] != 0 && $obj['return_type'] != "") {
            $wh[] = ['return_type', $obj['return_type']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['purchase_return_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['purchase_return_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];

        $datatable = $this->model_purchase_return->getModel()::with($this->with)
            ->withCount('purchaseReturnDetails as total_products')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('purchase_return_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('purchase_return_date', function ($item) {
                return !empty($item->purchase_return_date)
                    ? localDate($item->purchase_return_date)
                    : 'N/A';
            })
            ->addColumn('return_type', function ($item) {
                return $item->return_type === 'grn' ? 'GRN' : 'Direct Purchase';
            })
            ->addColumn('source_no', function ($item) {
                return $item->return_type === 'grn'
                    ? ($item->goodReceiptNote->good_receipt_note_no ?? '')
                    : ($item->purchase->purchase_no ?? '');
            })
            ->addColumn('supplier', function ($item) {
                return ($item->supplier->code ?? '') . ' ' . ($item->supplier->name ?? '');
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
                    Status::CANCELLED => ucfirst(Status::CANCELLED),
                ];

                $html = "<select class='form-select form-select-sm change-status'
                data-id='{$item->purchase_return_id}'>";

                foreach ($statuses as $value => $label) {
                    $selected = $item->status == $value ? 'selected' : '';
                    $html .= "<option value='{$value}' {$selected}>{$label}</option>";
                }

                $html .= "</select>";

                return $html;
            })
            ->addColumn('action', function ($item) {

                $editButton = $item->status === Status::PENDING
                    ? "<a class='btn btn-icon btn-outline-primary mr-2'
                        href='" . route('purchase-return.edit', $item->purchase_return_id) . "'
                        id='editPurchaseReturn'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='Only pending purchase returns can be edited'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                $printButton = "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('purchase-return.print', $item->purchase_return_id) . "' title='Print'>
                    <i class='fa fa-print'></i>
                    </a>";

                $deleteButton = $item->status !== Status::CANCELLED
                    ? "<a class='btn btn-icon btn-outline-danger'
                    id='deletePurchaseReturn'
                    data-id='{$item->purchase_return_id}'>
                    <i class='fa fa-trash'></i>
                    </a>"
                    : '';

                return $editButton . $printButton . $deleteButton;
            })
            ->rawColumns(['purchase_return_date', 'return_type', 'source_no', 'business', 'branch', 'warehouse', 'supplier', 'total_products', 'total', 'status', 'action'])
            ->make(true);
    }

    /**
     * Sum of return_quantity across every posted (approved) Purchase Return
     * line for this purchase line, scoped to a specific GRN when returning
     * against a GRN (the same purchase_detail_id can be spread across
     * multiple GRNs over time) or to direct-purchase returns only when not.
     */
    protected function getAlreadyReturnedQuantity($purchase_detail_id, $good_receipt_note_id = null)
    {
        $query = PurchaseReturnDetail::query()
            ->join('purchase_returns', 'purchase_returns.purchase_return_id', '=', 'purchase_return_details.purchase_return_id')
            ->where('purchase_return_details.purchase_detail_id', $purchase_detail_id)
            ->where('purchase_returns.status', Status::APPROVED)
            ->where('purchase_returns.is_deleted', 0);

        if (!empty($good_receipt_note_id)) {
            $query->where('purchase_return_details.good_receipt_note_id', $good_receipt_note_id);
        } else {
            $query->whereNull('purchase_return_details.good_receipt_note_id');
        }

        return (float) $query->sum('purchase_return_details.return_quantity');
    }

    public function getSourceLines($return_type, $source_id)
    {
        if ($return_type === 'direct') {
            return $this->getDirectPurchaseLines($source_id);
        }

        if ($return_type === 'grn') {
            return $this->getGrnLines($source_id);
        }

        throw new Exception('Invalid return type.');
    }

    /**
     * Fresh returnable lines for a direct Purchase, used to seed a new
     * Purchase Return's product rows.
     */
    protected function getDirectPurchaseLines($purchase_id)
    {
        $purchase = Purchase::with([
            'supplier',
            'warehouse',
            'purchaseDetails',
            'purchaseDetails.product',
            'purchaseDetails.productVariation',
            'purchaseDetails.unit',
        ])->findOrFail($purchase_id);

        if ($purchase->purchase_type !== 'direct' || $purchase->status !== Status::APPROVED) {
            throw new Exception('This purchase is not eligible for a Purchase Return.');
        }

        $lines = [];

        foreach ($purchase->purchaseDetails as $detail) {
            $received_quantity = (float) ($detail->received_quantity ?? 0);
            $already_returned = $this->getAlreadyReturnedQuantity($detail->purchase_detail_id, null);
            $returnable = $received_quantity - $already_returned;

            if ($returnable <= 0) {
                continue;
            }

            $lines[] = [
                'purchase_detail_id'                   => $detail->purchase_detail_id,
                'good_receipt_note_detail_id'          => null,
                'product_id'                            => $detail->product_id,
                'product_name'                          => $detail->product->name ?? '',
                'product_variation_id'                  => $detail->product_variation_id,
                'product_variation_name'                => $detail->productVariation->name ?? '',
                'product_variation_unit_conversion_id'  => $detail->product_variation_unit_conversion_id,
                'received_quantity'                     => $received_quantity,
                'already_returned_quantity'             => $already_returned,
                'returnable_quantity'                   => $returnable,
                'unit_id'                                => $detail->unit_id,
                'unit_name'                              => $detail->unit->name ?? 'N/A',
                'conversion_factor'                      => $detail->conversion_factor,
                'unit_price'                             => $detail->unit_price,
                'discount'                               => $detail->discount ?? 0,
                'tax'                                    => $detail->tax ?? 0,
            ];
        }

        return [
            'header' => [
                'return_type'          => 'direct',
                'purchase_id'          => $purchase->purchase_id,
                'good_receipt_note_id' => null,
                'source_no'            => $purchase->purchase_no,
                'supplier_id'          => $purchase->supplier_id,
                'supplier_name'        => $purchase->supplier->name ?? '',
                'warehouse_id'         => $purchase->warehouse_id,
                'warehouse_name'       => $purchase->warehouse->name ?? '',
                'business_id'          => $purchase->business_id,
                'branch_id'            => $purchase->branch_id,
            ],
            'lines' => $lines,
        ];
    }

    /**
     * Fresh returnable lines for a GRN, used to seed a new Purchase Return's
     * product rows. Returnable quantity is scoped to this specific GRN since
     * the same purchase line can be spread across multiple GRNs.
     */
    protected function getGrnLines($good_receipt_note_id)
    {
        $grn = GoodReceiptNote::with([
            'supplier',
            'warehouse',
            'purchase',
            'goodReceiptNoteDetails',
            'goodReceiptNoteDetails.product',
            'goodReceiptNoteDetails.productVariation',
            'goodReceiptNoteDetails.unit',
            'goodReceiptNoteDetails.purchaseDetail',
        ])->findOrFail($good_receipt_note_id);

        if ($grn->status !== Status::APPROVED) {
            throw new Exception('This GRN is not eligible for a Purchase Return.');
        }

        $lines = [];

        foreach ($grn->goodReceiptNoteDetails as $detail) {
            $received_quantity = (float) ($detail->received_quantity ?? 0);
            $already_returned = $this->getAlreadyReturnedQuantity($detail->purchase_detail_id, $grn->good_receipt_note_id);
            $returnable = $received_quantity - $already_returned;

            if ($returnable <= 0) {
                continue;
            }

            $purchase_detail = $detail->purchaseDetail;

            $lines[] = [
                'purchase_detail_id'                   => $detail->purchase_detail_id,
                'good_receipt_note_detail_id'          => $detail->good_receipt_note_detail_id,
                'product_id'                            => $detail->product_id,
                'product_name'                          => $detail->product->name ?? '',
                'product_variation_id'                  => $detail->product_variation_id,
                'product_variation_name'                => $detail->productVariation->name ?? '',
                'product_variation_unit_conversion_id'  => $detail->product_variation_unit_conversion_id,
                'received_quantity'                     => $received_quantity,
                'already_returned_quantity'             => $already_returned,
                'returnable_quantity'                   => $returnable,
                'unit_id'                                => $detail->unit_id,
                'unit_name'                              => $detail->unit->name ?? 'N/A',
                'conversion_factor'                      => $detail->conversion_factor,
                'unit_price'                             => $detail->unit_price,
                'discount'                               => $purchase_detail->discount ?? 0,
                'tax'                                    => $purchase_detail->tax ?? 0,
            ];
        }

        return [
            'header' => [
                'return_type'          => 'grn',
                'purchase_id'          => $grn->purchase_id,
                'good_receipt_note_id' => $grn->good_receipt_note_id,
                'source_no'            => $grn->good_receipt_note_no,
                'supplier_id'          => $grn->supplier_id,
                'supplier_name'        => $grn->supplier->name ?? '',
                'warehouse_id'         => $grn->warehouse_id,
                'warehouse_name'       => $grn->warehouse->name ?? '',
                'business_id'          => $grn->business_id,
                'branch_id'            => $grn->branch_id,
            ],
            'lines' => $lines,
        ];
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {
            $return_type = $obj['return_type'] ?? null;

            if (!in_array($return_type, ['direct', 'grn'], true)) {
                throw new Exception('Invalid return type.');
            }

            $purchase = null;
            $grn = null;

            if ($return_type === 'direct') {
                $purchase = Purchase::with('purchaseDetails.product')->find($obj['purchase_id'] ?? null);

                if (!$purchase || $purchase->purchase_type !== 'direct') {
                    throw new Exception('A direct Purchase Return can only be created against a direct purchase.');
                }

                if ($purchase->status !== Status::APPROVED) {
                    throw new Exception('A Purchase Return can only be created against an approved purchase.');
                }
            } else {
                $grn = GoodReceiptNote::with(['goodReceiptNoteDetails.product', 'purchase'])->find($obj['good_receipt_note_id'] ?? null);

                if (!$grn) {
                    throw new Exception('The selected GRN was not found.');
                }

                if ($grn->status !== Status::APPROVED) {
                    throw new Exception('A Purchase Return can only be created against an approved GRN.');
                }

                $purchase = $grn->purchase;

                if (!$purchase) {
                    throw new Exception('The GRN is not linked to a valid purchase.');
                }
            }

            $business_id = $purchase->business_id;
            $branch_id = $purchase->branch_id;
            $supplier_id = $purchase->supplier_id;
            $warehouse_id = $purchase->warehouse_id;

            //====================================
            // Update
            //====================================

            if (!empty($obj['purchase_return_id'])) {

                $purchase_return = $this->model_purchase_return->getModel()::findOrFail($obj['purchase_return_id']);

                if ($purchase_return->status !== Status::PENDING) {
                    throw new Exception('Only pending purchase returns can be updated.');
                }

                $purchase_return->update([
                    'business_id'            => $business_id,
                    'branch_id'              => $branch_id,
                    'supplier_id'            => $supplier_id,
                    'warehouse_id'           => $warehouse_id,
                    'purchase_id'            => $purchase->purchase_id,
                    'good_receipt_note_id'   => $grn->good_receipt_note_id ?? null,
                    'return_type'            => $return_type,
                    'purchase_return_date'   => $obj['purchase_return_date'],
                    'reason'                 => $obj['reason'] ?? null,
                    'description'            => $obj['description'] ?? null,
                    'updatedby_id'           => Auth::id(),
                    'date_updated'           => now(),
                ]);

                $this->model_purchase_return_details->getModel()::where('purchase_return_id', $purchase_return->purchase_return_id)
                    ->delete();
            }

            //====================================
            // Create
            //====================================

            else {

                $purchase_return = $this->model_purchase_return->create([
                    'purchase_return_id'    => generateUuid(),
                    'business_id'            => $business_id,
                    'branch_id'              => $branch_id,
                    'supplier_id'            => $supplier_id,
                    'warehouse_id'           => $warehouse_id,
                    'purchase_id'            => $purchase->purchase_id,
                    'good_receipt_note_id'   => $grn->good_receipt_note_id ?? null,
                    'return_type'            => $return_type,
                    'purchase_return_no'     => $obj['purchase_return_no'],
                    'purchase_return_date'   => $obj['purchase_return_date'],
                    'reason'                 => $obj['reason'] ?? null,
                    'description'            => $obj['description'] ?? null,
                    'status'                 => Status::PENDING,
                    'createdby_id'           => Auth::id(),
                    'date_created'           => now(),
                ]);
            }

            //====================================
            // Save Items
            //====================================

            $subtotal = 0;
            $discount_amount_total = 0;
            $tax_amount_total = 0;
            $total = 0;
            $has_quantity = false;

            foreach ($obj['products'] as $product) {

                if ($return_type === 'direct') {
                    $purchase_detail = $purchase->purchaseDetails
                        ->firstWhere('purchase_detail_id', $product['purchase_detail_id']);

                    if (!$purchase_detail) {
                        throw new Exception('One of the selected lines does not belong to the chosen purchase.');
                    }

                    $received_quantity = (float) ($purchase_detail->received_quantity ?? 0);
                    $already_returned = $this->getAlreadyReturnedQuantity($purchase_detail->purchase_detail_id, null);
                    $good_receipt_note_detail_id = null;
                    $conversion_factor = $purchase_detail->conversion_factor > 0 ? $purchase_detail->conversion_factor : 1;
                    $unit_price = (float) $purchase_detail->unit_price;
                    $discount_percent = (float) ($purchase_detail->discount ?? 0);
                    $tax_percent = (float) ($purchase_detail->tax ?? 0);
                    $product_name = $purchase_detail->product->name ?? 'a product';
                } else {
                    $grn_detail = $grn->goodReceiptNoteDetails
                        ->firstWhere('good_receipt_note_detail_id', $product['good_receipt_note_detail_id'] ?? null);

                    if (!$grn_detail) {
                        throw new Exception('One of the selected lines does not belong to the chosen GRN.');
                    }

                    $purchase_detail = PurchaseDetail::find($grn_detail->purchase_detail_id);

                    if (!$purchase_detail) {
                        throw new Exception('The originating purchase line could not be found.');
                    }

                    $received_quantity = (float) ($grn_detail->received_quantity ?? 0);
                    $already_returned = $this->getAlreadyReturnedQuantity($grn_detail->purchase_detail_id, $grn->good_receipt_note_id);
                    $good_receipt_note_detail_id = $grn_detail->good_receipt_note_detail_id;
                    $conversion_factor = $grn_detail->conversion_factor > 0 ? $grn_detail->conversion_factor : 1;
                    $unit_price = (float) $grn_detail->unit_price;
                    $discount_percent = (float) ($purchase_detail->discount ?? 0);
                    $tax_percent = (float) ($purchase_detail->tax ?? 0);
                    $product_name = $grn_detail->product->name ?? 'a product';
                }

                $return_quantity = (float) ($product['return_quantity'] ?? 0);

                if ($return_quantity < 0) {
                    throw new Exception('Return quantity cannot be negative.');
                }

                $returnable = $received_quantity - $already_returned;

                if ($return_quantity > $returnable) {
                    throw new Exception('Return quantity for "' . $product_name . '" exceeds the returnable quantity.');
                }

                if ($return_quantity > 0) {
                    $has_quantity = true;
                }

                $base_quantity = $return_quantity * $conversion_factor;

                $line_subtotal = $base_quantity * $unit_price;
                $line_discount_amount = round($line_subtotal * $discount_percent / 100, 3);
                $taxable = $line_subtotal - $line_discount_amount;
                $line_tax_amount = round($taxable * $tax_percent / 100, 3);
                $line_total = $taxable + $line_tax_amount;

                $subtotal += $line_subtotal;
                $discount_amount_total += $line_discount_amount;
                $tax_amount_total += $line_tax_amount;
                $total += $line_total;

                $this->model_purchase_return_details->create([
                    'purchase_return_detail_id'             => generateUuid(),
                    'purchase_return_id'                    => $purchase_return->purchase_return_id,
                    'purchase_id'                            => $purchase->purchase_id,
                    'purchase_detail_id'                     => $purchase_detail->purchase_detail_id,
                    'good_receipt_note_id'                   => $grn->good_receipt_note_id ?? null,
                    'good_receipt_note_detail_id'             => $good_receipt_note_detail_id,
                    'product_id'                              => $purchase_detail->product_id,
                    'product_variation_id'                    => $purchase_detail->product_variation_id,
                    'product_variation_unit_conversion_id'    => $purchase_detail->product_variation_unit_conversion_id,
                    'unit_id'                                  => $purchase_detail->unit_id,
                    'received_quantity'                       => $received_quantity,
                    'already_returned_quantity'               => $already_returned,
                    'return_quantity'                         => $return_quantity,
                    'conversion_factor'                       => $conversion_factor,
                    'base_quantity'                           => $base_quantity,
                    'unit_price'                               => $unit_price,
                    'discount'                                 => $discount_percent,
                    'discount_amount'                          => $line_discount_amount,
                    'tax'                                      => $tax_percent,
                    'tax_amount'                               => $line_tax_amount,
                    'subtotal'                                 => $line_subtotal,
                    'total'                                    => $line_total,
                    'reason'                                   => $product['reason'] ?? null,
                    'description'                              => $product['description'] ?? null,
                    'createdby_id'                             => Auth::id(),
                    'date_created'                             => now(),
                ]);
            }

            if (!$has_quantity) {
                throw new Exception('Please enter a return quantity for at least one product.');
            }

            $purchase_return->update([
                'subtotal'         => $subtotal,
                'discount_amount'  => $discount_amount_total,
                'tax_amount'       => $tax_amount_total,
                'total'            => $total,
            ]);

            DB::commit();

            return $purchase_return;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($purchase_return_id)
    {
        return $this->model_purchase_return->with($this->with)->find($purchase_return_id);
    }

    public function getDetails($purchase_return_id)
    {
        try {
            $purchase_return = $this->model_purchase_return->getModel()::with($this->with)->findOrFail($purchase_return_id);

            $data = [
                'header' => [
                    'purchase_return_id'   => $purchase_return->purchase_return_id,
                    'return_type'          => $purchase_return->return_type,
                    'purchase_id'          => $purchase_return->purchase_id,
                    'purchase_no'          => $purchase_return->purchase->purchase_no ?? '',
                    'good_receipt_note_id' => $purchase_return->good_receipt_note_id,
                    'good_receipt_note_no' => $purchase_return->goodReceiptNote->good_receipt_note_no ?? '',
                    'supplier_id'          => $purchase_return->supplier_id,
                    'warehouse_id'         => $purchase_return->warehouse_id,
                    'branch_id'            => $purchase_return->branch_id,
                    'purchase_return_no'   => $purchase_return->purchase_return_no,
                    'purchase_return_date' => $purchase_return->purchase_return_date,
                    'reason'               => $purchase_return->reason,
                    'description'          => $purchase_return->description,
                    'subtotal'             => $purchase_return->subtotal,
                    'discount_amount'      => $purchase_return->discount_amount,
                    'tax_amount'           => $purchase_return->tax_amount,
                    'total'                => $purchase_return->total,
                    'status'               => $purchase_return->status,
                ],
                'details' => []
            ];

            foreach ($purchase_return->purchaseReturnDetails as $detail) {
                $data['details'][] = [
                    'purchase_return_detail_id'   => $detail->purchase_return_detail_id,
                    'purchase_detail_id'          => $detail->purchase_detail_id,
                    'good_receipt_note_detail_id' => $detail->good_receipt_note_detail_id,
                    'product_id'                  => $detail->product_id,
                    'product_name'                => $detail->product->name ?? '',
                    'product_variation_id'        => $detail->product_variation_id,
                    'product_variation_name'      => $detail->productVariation->name ?? '',
                    'received_quantity'           => $detail->received_quantity,
                    'already_returned_quantity'   => $detail->already_returned_quantity,
                    'return_quantity'             => $detail->return_quantity,
                    'unit_id'                     => $detail->unit_id,
                    'unit_name'                   => $detail->unit->name ?? 'N/A',
                    'conversion_factor'           => $detail->conversion_factor,
                    'unit_price'                  => $detail->unit_price,
                    'discount'                    => $detail->discount,
                    'discount_amount'             => $detail->discount_amount,
                    'tax'                         => $detail->tax,
                    'tax_amount'                  => $detail->tax_amount,
                    'subtotal'                    => $detail->subtotal,
                    'total'                       => $detail->total,
                    'reason'                      => $detail->reason,
                ];
            }

            return $data;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function status($obj)
    {
        DB::beginTransaction();

        try {
            $purchase_return = $this->model_purchase_return->getModel()::with($this->with)->findOrFail($obj['purchase_return_id']);
            $old_status = $purchase_return->status;
            $new_status = $obj['status'];

            $purchase_return->update([
                'status'       => $new_status,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            if ($new_status === Status::APPROVED && $old_status !== Status::APPROVED) {
                $this->applyPurchaseReturnPosting($purchase_return);
            } elseif ($old_status === Status::APPROVED && $new_status !== Status::APPROVED) {
                $this->reversePurchaseReturnPosting($purchase_return);
            }

            DB::commit();

            $this->logActivity(
                'purchase_return',
                $purchase_return->purchase_return_id,
                $new_status === Status::APPROVED ? 'approved' : 'status_changed',
                ['status' => $old_status],
                ['status' => $new_status]
            );
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $purchase_return;
    }

    public function delete($purchase_return_id)
    {
        DB::beginTransaction();

        try {
            $purchase_return = $this->model_purchase_return->getModel()::with($this->with)->findOrFail($purchase_return_id);

            if ($purchase_return->status === Status::APPROVED) {
                $this->reversePurchaseReturnPosting($purchase_return);
            }

            $purchase_return->update([
                'is_deleted'   => 1,
                'status'       => Status::CANCELLED,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            DB::commit();

            $this->logActivity('purchase_return', $purchase_return->purchase_return_id, 'deleted');

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Auto-post a Purchase Return Voucher and stock-out transactions when a
     * Purchase Return is approved. Idempotent: a no-op if an active voucher
     * already exists for this return.
     */
    protected function applyPurchaseReturnPosting(PurchaseReturn $purchase_return)
    {
        $existing = JournalEntry::where('source_type', JournalSourceTypes::PURCHASE_RETURN)
            ->where('source_id', $purchase_return->purchase_return_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $purchase_return->business_id)->first();

        if (!$accounting_setting || !$accounting_setting->enable_accounting || empty($accounting_setting->default_purchase_return_account_id)) {
            throw new Exception('Purchase Return Account is not configured in Accounting Settings. Please configure it before approving purchase returns.');
        }

        app(\App\Services\Concrete\Admin\AccountingPeriodService::class)->assertPostable($purchase_return->business_id, now());

        if (empty($purchase_return->supplier) || empty($purchase_return->supplier->account_id)) {
            throw new Exception('The supplier does not have a linked Chart of Account. Please configure it before approving purchase returns.');
        }

        $journal = Journal::where('short', 'PRV')->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "Purchase Return Voucher" journal category found. Please configure it before approving purchase returns.');
        }

        $entry_no = generateJVNum($journal->journal_id);

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $purchase_return->business_id,
            'branch_id'        => $purchase_return->branch_id,
            'entry_no'         => $entry_no,
            'reference_no'     => $purchase_return->purchase_return_no,
            'entry_date'       => now(),
            'description'      => 'Auto-generated return voucher for approved purchase return ' . $purchase_return->purchase_return_no,
            'source_type'      => JournalSourceTypes::PURCHASE_RETURN,
            'source_id'        => $purchase_return->purchase_return_id,
            'status'           => 'posted',
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        $amount = $purchase_return->total;

        // Debit the supplier's payable account - a return reduces what we owe them.
        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $purchase_return->supplier->account_id,
            'debit'                   => $amount,
            'credit'                  => 0,
            'supplier_id'             => $purchase_return->supplier_id,
            'description'             => 'Purchase Return - ' . $purchase_return->purchase_return_no,
        ]);

        // Credit the Purchase Return account - contra to the original Purchase Account debit.
        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $accounting_setting->default_purchase_return_account_id,
            'debit'                   => 0,
            'credit'                  => $amount,
            'supplier_id'             => $purchase_return->supplier_id,
            'description'             => 'Purchase Return - ' . $purchase_return->purchase_return_no,
        ]);

        foreach ($purchase_return->purchaseReturnDetails as $detail) {
            $base_quantity = $detail->base_quantity;

            if ($base_quantity <= 0) {
                continue;
            }

            $stock = ProductVariationStock::where('business_id', $purchase_return->business_id)
                ->where('warehouse_id', $purchase_return->warehouse_id)
                ->where('product_id', $detail->product_id)
                ->where('product_variation_id', $detail->product_variation_id)
                ->first();

            $existing_avg = $stock->avg_price ?? 0;
            $new_qty = ($stock->quantity ?? 0) - $base_quantity;

            if ($stock) {
                $stock->update([
                    'quantity' => $new_qty,
                ]);
            } else {
                $stock = ProductVariationStock::create([
                    'product_variation_stock_id' => generateUuid(),
                    'business_id'                => $purchase_return->business_id,
                    'warehouse_id'               => $purchase_return->warehouse_id,
                    'product_id'                 => $detail->product_id,
                    'product_variation_id'       => $detail->product_variation_id,
                    'quantity'                   => $new_qty,
                    'avg_price'                  => 0,
                    'status'                     => 'active',
                    'createdby_id'               => Auth::id(),
                    'date_created'               => now(),
                ]);
            }

            ProductVariationStockTransaction::create([
                'product_variation_stock_transaction_id' => generateUuid(),
                'transaction_date'                       => now(),
                'transaction_type'                        => TransactionType::PURCHASE_RETURN,
                'business_id'                             => $purchase_return->business_id,
                'product_id'                              => $detail->product_id,
                'product_variation_id'                    => $detail->product_variation_id,
                'warehouse_id'                             => $purchase_return->warehouse_id,
                'unit_id'                                  => $detail->unit_id,
                'product_variation_unit_conversion_id'     => $detail->product_variation_unit_conversion_id,
                'conversion_factor'                        => $detail->conversion_factor,
                'quantity'                                 => $detail->return_quantity,
                'base_quantity'                            => $base_quantity,
                'unit_price'                               => $detail->unit_price,
                'total_price'                              => $detail->unit_price * $base_quantity,
                'quantity_after'                           => $new_qty,
                'avg_price_after'                          => $existing_avg,
                'reference_id'                              => $purchase_return->purchase_return_id,
                'reference_type'                            => ReferenceType::PURCHASE_RETURN,
                'remarks'                                   => 'Auto-created on approval of purchase return ' . $purchase_return->purchase_return_no,
                'createdby_id'                              => Auth::id(),
                'date_created'                              => now(),
            ]);
        }
    }

    /**
     * Reverse the Purchase Return Voucher and stock effects created when a
     * Purchase Return was approved. Idempotent: a no-op if nothing active
     * remains to reverse.
     */
    protected function reversePurchaseReturnPosting(PurchaseReturn $purchase_return)
    {
        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::PURCHASE_RETURN)
            ->where('source_id', $purchase_return->purchase_return_id)
            ->where('is_deleted', 0)
            ->first();

        if ($journal_entry) {
            $journal_entry->update([
                'is_deleted'   => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);
        }

        $stock_transactions = ProductVariationStockTransaction::where('reference_type', ReferenceType::PURCHASE_RETURN)
            ->where('reference_id', $purchase_return->purchase_return_id)
            ->where('is_deleted', 0)
            ->get();

        if ($stock_transactions->isEmpty()) {
            return;
        }

        $stock_transactions->each(function ($transaction) {
            $transaction->update([
                'is_deleted'   => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);
        });

        $affected = $stock_transactions->unique(function ($transaction) {
            return $transaction->business_id . '|' . $transaction->warehouse_id . '|' .
                $transaction->product_id . '|' . $transaction->product_variation_id;
        });

        $stock_service = app(ProductVariationStockService::class);

        foreach ($affected as $transaction) {
            $stock_service->recomputeLedger(
                $transaction->business_id,
                $transaction->warehouse_id,
                $transaction->product_id,
                $transaction->product_variation_id
            );
        }
    }

    public function getByBusiness($business_id)
    {
        return $this->model_purchase_return->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
