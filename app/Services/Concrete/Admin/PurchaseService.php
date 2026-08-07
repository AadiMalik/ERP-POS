<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\JournalSourceTypes;
use App\Enums\ReferenceType;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Models\AccountingSetting;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\ProductVariationStock;
use App\Models\ProductVariationStockTransaction;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseRequest;
use App\Repository\Repository;
use App\Services\Concrete\Admin\ProductVariationStockService;
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
        'purchaseRequest',
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
        if (isset($obj['purchase_request_id']) && $obj['purchase_request_id'] != 0 && $obj['purchase_request_id'] != "") {
            $wh[] = ['purchase_request_id', $obj['purchase_request_id']];
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
            ->addColumn('purchase_request_no', function ($item) {
                return $item->purchase_request->purchase_request_no ?? '';
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

                $editButton = $item->status === Status::PENDING
                    ? "<a class='btn btn-icon btn-outline-primary mr-2'
                        href='" . route('purchase.edit', $item->purchase_id) . "'
                        id='editPurchase'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='Only pending purchases can be edited'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                return $editButton . "
                    <a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('purchase.print', $item->purchase_id) . "' title='Print'>
                    <i class='fa fa-print'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deletePurchase'
                    data-id='{$item->purchase_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['purchase_date', 'purchase_request_no', 'business', 'branch', 'warehouse', 'supplier', 'total_products', 'total',  'status', 'action'])
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

                $purchase = $this->model_purchase
                    ->getModel()::findOrFail($obj['purchase_id']);

                if ($purchase->status !== Status::PENDING) {
                    throw new Exception('Only pending purchases can be updated.');
                }

                $purchase->update([
                    'business_id'            => $obj['business_id'],
                    'purchase_request_id'    => $obj['purchase_request_id'],
                    'purchase_type'          => $obj['purchase_type'] ?? $purchase->purchase_type,
                    'supplier_id'            => $obj['supplier_id'],
                    'warehouse_id'           => $obj['warehouse_id'],
                    'purchase_date'          => $obj['purchase_date'],
                    'expected_delivery_date' => $obj['expected_delivery_date'],
                    'description'            => $obj['description'],
                    'subtotal'               => $obj['subtotal'],
                    'discount'               => $obj['discount'] ?? 0,
                    'discount_amount'        => $obj['discount_amount'],
                    'tax'                    => $obj['tax'] ?? 0,
                    'tax_amount'             => $obj['tax_amount'],
                    'shipping_charge'        => $obj['shipping_charge'],
                    'total'                  => $obj['total'],
                    'updatedby_id'           => Auth::user()->id,
                    'date_updated'           => now(),
                ]);

                // Remove previous items

                $this->model_purchase_details->getModel()::where('purchase_id', $purchase->purchase_id)
                    ->delete();
            }

            //====================================
            // Create
            //====================================

            else {

                $purchase = $this->model_purchase->create([
                    'purchase_id'            => generateUuid(),
                    'business_id'            => $obj['business_id'],
                    'purchase_request_id'    => $obj['purchase_request_id'],
                    'purchase_type'          => $obj['purchase_type'] ?? 'direct',
                    'supplier_id'            => $obj['supplier_id'],
                    'warehouse_id'           => $obj['warehouse_id'],
                    'purchase_no'            => $obj['purchase_no'],
                    'purchase_date'          => $obj['purchase_date'],
                    'expected_delivery_date' => $obj['expected_delivery_date'],
                    'description'            => $obj['description'],
                    'subtotal'               => $obj['subtotal'],
                    'discount'               => $obj['discount'] ?? 0,
                    'discount_amount'        => $obj['discount_amount'],
                    'tax'                    => $obj['tax'] ?? 0,
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

                    'purchase_detail_id'                    => generateUuid(),
                    'purchase_id'                           => $purchase->purchase_id,
                    'purchase_request_detail_id'            => isset($product['purchase_request_detail_id']) ? $product['purchase_request_detail_id'] : null,

                    'product_id'                            => $product['product_id'],
                    'product_variation_id'                  => $product['product_variation_id'],
                    'product_variation_unit_conversion_id'  => $product['product_variation_unit_conversion_id'],

                    'unit_id'                               => $product['unit_id'],
                    'base_quantity'                         => $product['ordered_quantity'] * $product['conversion_factor'],
                    'ordered_quantity'                      => $product['ordered_quantity'],
                    'received_quantity'                     => $product['received_quantity'] ?? $product['ordered_quantity'],
                    'conversion_factor'                     => $product['conversion_factor'],
                    'unit_price'                            => $product['unit_price'],
                    'subtotal'                              => $product['subtotal'],
                    'discount'                              => $product['discount'],
                    'discount_amount'                       => $product['discount_amount'],
                    'tax'                                   => $product['tax'],
                    'tax_amount'                            => $product['tax_amount'],
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
        DB::beginTransaction();

        try {
            $purchase = $this->model_purchase->getModel()::with($this->with)->findOrFail($obj['purchase_id']);
            $old_status = $purchase->status;
            $new_status = $obj['status'];

            $purchase->update([
                'status' => $new_status,
                'updatedby_id' => Auth::user()->id,
                'date_updated' => now()
            ]);

            if ($new_status == Status::APPROVED && $purchase->purchase_request_id != null) {
                PurchaseRequest::where('purchase_request_id', $purchase->purchase_request_id)
                    ->update([
                        'status' => Status::CONVERTED,
                        'updatedby_id' => Auth::user()->id,
                        'date_updated' => now()
                    ]);
            }

            if ($purchase->purchase_type === 'direct') {
                if ($new_status === Status::APPROVED && $old_status !== Status::APPROVED) {
                    $this->applyDirectPurchaseApproval($purchase);
                } elseif (
                    $old_status === Status::APPROVED &&
                    $new_status !== Status::APPROVED &&
                    $new_status !== Status::COMPLETED
                ) {
                    $this->reverseDirectPurchaseApproval($purchase);
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $purchase;
    }

    public function delete($purchase_id)
    {
        DB::beginTransaction();

        try {
            $purchase = $this->model_purchase->getModel()::with($this->with)->findOrFail($purchase_id);

            if ($purchase->purchase_type === 'direct' && $purchase->status === Status::APPROVED) {
                $this->reverseDirectPurchaseApproval($purchase);
            }

            $purchase->update([
                'is_deleted' => 1,
                'status' => Status::CANCELLED,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now()
            ]);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Auto-post a Purchase Voucher and stock-in transactions when a Direct
     * Purchase is approved. Idempotent: a no-op if an active voucher already
     * exists for this purchase (e.g. duplicate status calls).
     */
    protected function applyDirectPurchaseApproval(Purchase $purchase)
    {
        $existing = JournalEntry::where('source_type', JournalSourceTypes::PURCHASE)
            ->where('source_id', $purchase->purchase_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $purchase->business_id)->first();

        if (!$accounting_setting || !$accounting_setting->enable_accounting || empty($accounting_setting->default_purchase_account_id)) {
            throw new Exception('Purchase Account is not configured in Accounting Settings. Please configure it before approving direct purchases.');
        }

        if (empty($purchase->supplier) || empty($purchase->supplier->account_id)) {
            throw new Exception('The selected supplier does not have a linked Chart of Account. Please configure it before approving direct purchases.');
        }

        $journal = Journal::where('short', 'PV')->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "Purchase Voucher" journal category found. Please configure it before approving direct purchases.');
        }

        $entry_no = generateJVNum($journal->journal_id);

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $purchase->business_id,
            'branch_id'        => $purchase->branch_id,
            'entry_no'         => $entry_no,
            'reference_no'     => $purchase->purchase_no,
            'entry_date'       => now(),
            'description'      => 'Auto-generated payment voucher for approved direct purchase ' . $purchase->purchase_no,
            'source_type'      => JournalSourceTypes::PURCHASE,
            'source_id'        => $purchase->purchase_id,
            'status'           => 'posted',
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        $amount = $purchase->total;

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $accounting_setting->default_purchase_account_id,
            'debit'                   => $amount,
            'credit'                  => 0,
            'supplier_id'             => $purchase->supplier_id,
            'description'             => 'Purchase - ' . $purchase->purchase_no,
        ]);

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $purchase->supplier->account_id,
            'debit'                   => 0,
            'credit'                  => $amount,
            'supplier_id'             => $purchase->supplier_id,
            'description'             => 'Purchase - ' . $purchase->purchase_no,
        ]);

        foreach ($purchase->purchaseDetails as $detail) {
            $conversion_factor = $detail->conversion_factor > 0 ? $detail->conversion_factor : 1;
            $quantity = $detail->received_quantity ?? $detail->ordered_quantity;
            $base_quantity = $quantity * $conversion_factor;

            if ($base_quantity <= 0) {
                continue;
            }

            // unit_price is priced per base unit (subtotal = ordered_quantity *
            // conversion_factor * unit_price), so line cost scales correctly
            // even when received_quantity differs from ordered_quantity.
            $line_cost = $detail->unit_price * $base_quantity;

            $stock = ProductVariationStock::where('business_id', $purchase->business_id)
                ->where('warehouse_id', $purchase->warehouse_id)
                ->where('product_id', $detail->product_id)
                ->where('product_variation_id', $detail->product_variation_id)
                ->first();

            $existing_qty = $stock->quantity ?? 0;
            $existing_avg = $stock->avg_price ?? 0;
            $new_qty = $existing_qty + $base_quantity;
            $new_avg = $new_qty > 0
                ? (($existing_qty * $existing_avg) + $line_cost) / $new_qty
                : 0;

            if ($stock) {
                $stock->update([
                    'quantity'  => $new_qty,
                    'avg_price' => $new_avg,
                ]);
            } else {
                $stock = ProductVariationStock::create([
                    'product_variation_stock_id' => generateUuid(),
                    'business_id'                => $purchase->business_id,
                    'warehouse_id'               => $purchase->warehouse_id,
                    'product_id'                 => $detail->product_id,
                    'product_variation_id'       => $detail->product_variation_id,
                    'quantity'                   => $new_qty,
                    'avg_price'                  => $new_avg,
                    'status'                     => 'active',
                    'createdby_id'               => Auth::id(),
                    'date_created'               => now(),
                ]);
            }

            ProductVariationStockTransaction::create([
                'product_variation_stock_transaction_id' => generateUuid(),
                'transaction_date'                       => now(),
                'transaction_type'                        => TransactionType::PURCHASE,
                'business_id'                             => $purchase->business_id,
                'product_id'                              => $detail->product_id,
                'product_variation_id'                    => $detail->product_variation_id,
                'warehouse_id'                             => $purchase->warehouse_id,
                'unit_id'                                  => $detail->unit_id,
                'product_variation_unit_conversion_id'     => $detail->product_variation_unit_conversion_id,
                'conversion_factor'                        => $conversion_factor,
                'quantity'                                 => $quantity,
                'base_quantity'                            => $base_quantity,
                'unit_price'                               => $detail->unit_price,
                'total_price'                              => $line_cost,
                'quantity_after'                           => $new_qty,
                'avg_price_after'                          => $new_avg,
                'reference_id'                              => $purchase->purchase_id,
                'reference_type'                            => ReferenceType::PURCHASE,
                'remarks'                                   => 'Auto-created on approval of direct purchase ' . $purchase->purchase_no,
                'createdby_id'                              => Auth::id(),
                'date_created'                              => now(),
            ]);
        }
    }

    /**
     * Reverse the Purchase Voucher and stock effects created when a Direct
     * Purchase was approved. Idempotent: a no-op if nothing active remains
     * to reverse (e.g. already reversed, or never approved).
     */
    protected function reverseDirectPurchaseApproval(Purchase $purchase)
    {
        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::PURCHASE)
            ->where('source_id', $purchase->purchase_id)
            ->where('is_deleted', 0)
            ->first();

        if ($journal_entry) {
            $journal_entry->update([
                'is_deleted'   => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);
        }

        $stock_transactions = ProductVariationStockTransaction::where('reference_type', ReferenceType::PURCHASE)
            ->where('reference_id', $purchase->purchase_id)
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

        // Recompute each affected stock row (and rewrite the running-balance
        // snapshot on every remaining transaction) from scratch rather than
        // reversing the moving-average formula in place, so both the Stock
        // table and the stock ledger stay exact and in sync.
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

    /**
     * Compare ordered vs received quantity across every line of a Purchase
     * and flip the Purchase to Completed once every line is fully received,
     * or back to Approved if a reversal leaves a line short again. Only acts
     * on purchases currently Approved or Completed; never touches
     * pending/cancelled purchases. Called after every GRN approval/reversal.
     */
    public function syncPurchaseCompletionStatus($purchase_id)
    {
        $purchase = $this->model_purchase->getModel()::find($purchase_id);

        if (!$purchase || !in_array($purchase->status, [Status::APPROVED, Status::COMPLETED], true)) {
            return;
        }

        $fully_received = PurchaseDetail::where('purchase_id', $purchase_id)
            ->where(function ($query) {
                $query->whereColumn('received_quantity', '<', 'ordered_quantity')
                    ->orWhereNull('received_quantity');
            })
            ->doesntExist();

        $new_status = $fully_received ? Status::COMPLETED : Status::APPROVED;

        if ($purchase->status !== $new_status) {
            $purchase->update([
                'status'       => $new_status,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);
        }
    }

    public function getAll()
    {
        return $this->model_purchase->getModel()::where('is_deleted', 0)
            ->get();
    }
    public function getDetails($purchase_id)
    {
        try {
            $purchase = $this->model_purchase->getModel()::with($this->with)->findOrFail($purchase_id);

            $data = [
                'header' => [
                    'purchase_id' => $purchase->purchase_id,
                    'supplier_id' => $purchase->supplier_id,
                    'warehouse_id' => $purchase->warehouse_id,
                    'branch_id' => $purchase->branch_id,
                    'purchase_no' => $purchase->purchase_no,
                    'purchase_date' => $purchase->purchase_date,
                    'purchase_expected_date' => $purchase->purchase_expected_date,
                    'subtotal' => $purchase->subtotal,
                    'discount' => $purchase->discount,
                    'tax' => $purchase->tax,
                    'shipping_charge' => $purchase->shipping_charge,
                    'description' => $purchase->description,
                ],
                'details' => []
            ];

            foreach ($purchase->purchaseDetails as $detail) {
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
                    'purchase_request_detail_id' => $detail->purchase_request_detail_id,
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
                    'subtotal' => $detail->subtotal,
                    'discount' => $detail->discount,
                    'discount_amount' => $detail->discount_amount,
                    'tax' => $detail->tax,
                    'tax_amount' => $detail->tax_amount,
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
