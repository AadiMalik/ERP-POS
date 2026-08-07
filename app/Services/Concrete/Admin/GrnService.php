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
use App\Models\GoodReceiptNoteDetail;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\ProductVariationStock;
use App\Models\ProductVariationStockTransaction;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class GrnService
{
    protected $model_grn;
    protected $model_grn_details;
    protected $with = [
        'business',
        'branch',
        'supplier',
        'warehouse',
        'purchase',
        'goodReceiptNoteDetails',
        'goodReceiptNoteDetails.product',
        'goodReceiptNoteDetails.productVariation',
        'goodReceiptNoteDetails.productVariationUnitConversion',
        'goodReceiptNoteDetails.unit',
        'goodReceiptNoteDetails.purchaseDetail',
    ];

    public function __construct()
    {
        $this->model_grn = new Repository(new GoodReceiptNote());
        $this->model_grn_details = new Repository(new GoodReceiptNoteDetail());
    }

    /**
     * Purchases eligible to receive a GRN against: generated from a Purchase
     * Request, currently Approved (not yet Completed), and still carrying at
     * least one line whose received_quantity hasn't caught up to ordered_quantity.
     */
    public function getEligiblePurchases($business_id = null)
    {
        $query = Purchase::with(['supplier', 'warehouse', 'purchaseDetails'])
            ->where('purchase_type', 'purchase_request')
            ->where('status', Status::APPROVED)
            ->where('is_deleted', 0)
            ->whereHas('purchaseDetails', function ($q) {
                $q->whereColumn('received_quantity', '<', 'ordered_quantity')
                    ->orWhereNull('received_quantity');
            });

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
        if (isset($obj['purchase_id']) && $obj['purchase_id'] != 0 && $obj['purchase_id'] != "") {
            $wh[] = ['purchase_id', $obj['purchase_id']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['good_receipt_note_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['good_receipt_note_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];

        $datatable = $this->model_grn->getModel()::with($this->with)
            ->withCount('goodReceiptNoteDetails as total_products')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('good_receipt_note_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('good_receipt_note_date', function ($item) {
                return !empty($item->good_receipt_note_date)
                    ? localDate($item->good_receipt_note_date)
                    : 'N/A';
            })
            ->addColumn('purchase_no', function ($item) {
                return $item->purchase->purchase_no ?? '';
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
                data-id='{$item->good_receipt_note_id}'>";

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
                        href='" . route('good-receipt-note.edit', $item->good_receipt_note_id) . "'
                        id='editGoodReceiptNote'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='Only pending GRNs can be edited'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                $printButton = "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('good-receipt-note.print', $item->good_receipt_note_id) . "' title='Print'>
                    <i class='fa fa-print'></i>
                    </a>";

                $deleteButton = $item->status !== Status::CANCELLED
                    ? "<a class='btn btn-icon btn-outline-danger'
                    id='deleteGoodReceiptNote'
                    data-id='{$item->good_receipt_note_id}'>
                    <i class='fa fa-trash'></i>
                    </a>"
                    : '';

                return $editButton . $printButton . $deleteButton;
            })
            ->rawColumns(['good_receipt_note_date', 'purchase_no', 'business', 'branch', 'warehouse', 'supplier', 'total_products', 'total', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {
            $purchase = Purchase::with('purchaseDetails')->find($obj['purchase_id']);

            if (!$purchase || $purchase->purchase_type !== 'purchase_request') {
                throw new Exception('A GRN can only be created against a purchase generated from a Purchase Request.');
            }

            if ($purchase->status !== Status::APPROVED) {
                throw new Exception('A GRN can only be created against an approved purchase that is not yet completed.');
            }

            //====================================
            // Update
            //====================================

            if (!empty($obj['good_receipt_note_id'])) {

                $grn = $this->model_grn->getModel()::findOrFail($obj['good_receipt_note_id']);

                if ($grn->status !== Status::PENDING) {
                    throw new Exception('Only pending GRNs can be updated.');
                }

                $grn->update([
                    'business_id'            => $purchase->business_id,
                    'branch_id'              => $purchase->branch_id,
                    'supplier_id'            => $purchase->supplier_id,
                    'purchase_id'            => $purchase->purchase_id,
                    'warehouse_id'           => $purchase->warehouse_id,
                    'good_receipt_note_date' => $obj['good_receipt_note_date'],
                    'description'            => $obj['description'] ?? null,
                    'updatedby_id'           => Auth::id(),
                    'date_updated'           => now(),
                ]);

                $this->model_grn_details->getModel()::where('good_receipt_note_id', $grn->good_receipt_note_id)
                    ->delete();
            }

            //====================================
            // Create
            //====================================

            else {

                $grn = $this->model_grn->create([
                    'good_receipt_note_id'   => generateUuid(),
                    'business_id'            => $purchase->business_id,
                    'branch_id'              => $purchase->branch_id,
                    'supplier_id'            => $purchase->supplier_id,
                    'purchase_id'            => $purchase->purchase_id,
                    'warehouse_id'           => $purchase->warehouse_id,
                    'good_receipt_note_no'   => $obj['good_receipt_note_no'],
                    'good_receipt_note_date' => $obj['good_receipt_note_date'],
                    'description'            => $obj['description'] ?? null,
                    'status'                 => Status::PENDING,
                    'createdby_id'           => Auth::id(),
                    'date_created'           => now(),
                ]);
            }

            //====================================
            // Save Items
            //====================================

            $total = 0;
            $has_quantity = false;

            foreach ($obj['products'] as $product) {

                $purchase_detail = $purchase->purchaseDetails
                    ->firstWhere('purchase_detail_id', $product['purchase_detail_id']);

                if (!$purchase_detail) {
                    throw new Exception('One of the selected lines does not belong to the chosen purchase.');
                }

                $received_quantity = (float) ($product['received_quantity'] ?? 0);

                if ($received_quantity < 0) {
                    throw new Exception('Received quantity cannot be negative.');
                }

                $already_received = (float) ($purchase_detail->received_quantity ?? 0);
                $remaining = (float) $purchase_detail->ordered_quantity - $already_received;

                if ($received_quantity > $remaining) {
                    throw new Exception('Received quantity for "' . ($purchase_detail->product->name ?? 'a product') . '" exceeds the remaining receivable quantity.');
                }

                if ($received_quantity > 0) {
                    $has_quantity = true;
                }

                $conversion_factor = $purchase_detail->conversion_factor > 0 ? $purchase_detail->conversion_factor : 1;
                $base_quantity = $received_quantity * $conversion_factor;
                $line_total = $base_quantity * $purchase_detail->unit_price;
                $total += $line_total;

                $this->model_grn_details->create([
                    'good_receipt_note_detail_id'          => generateUuid(),
                    'good_receipt_note_id'                 => $grn->good_receipt_note_id,
                    'purchase_id'                           => $purchase->purchase_id,
                    'purchase_detail_id'                    => $purchase_detail->purchase_detail_id,
                    'product_id'                            => $purchase_detail->product_id,
                    'product_variation_id'                  => $purchase_detail->product_variation_id,
                    'product_variation_unit_conversion_id'  => $purchase_detail->product_variation_unit_conversion_id,
                    'received_quantity'                     => $received_quantity,
                    'base_quantity'                         => $base_quantity,
                    'unit_id'                                => $purchase_detail->unit_id,
                    'conversion_factor'                      => $conversion_factor,
                    'unit_price'                              => $purchase_detail->unit_price,
                    'total'                                   => $line_total,
                ]);
            }

            if (!$has_quantity) {
                throw new Exception('Please receive at least one product.');
            }

            $grn->update(['total' => $total]);

            DB::commit();

            return $grn;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($good_receipt_note_id)
    {
        return $this->model_grn->with($this->with)->find($good_receipt_note_id);
    }

    public function getDetails($good_receipt_note_id)
    {
        try {
            $grn = $this->model_grn->getModel()::with($this->with)->findOrFail($good_receipt_note_id);

            $data = [
                'header' => [
                    'good_receipt_note_id'   => $grn->good_receipt_note_id,
                    'purchase_id'             => $grn->purchase_id,
                    'purchase_no'             => $grn->purchase->purchase_no ?? '',
                    'supplier_id'             => $grn->supplier_id,
                    'warehouse_id'            => $grn->warehouse_id,
                    'branch_id'               => $grn->branch_id,
                    'good_receipt_note_no'    => $grn->good_receipt_note_no,
                    'good_receipt_note_date'  => $grn->good_receipt_note_date,
                    'description'             => $grn->description,
                    'total'                   => $grn->total,
                    'status'                  => $grn->status,
                ],
                'details' => []
            ];

            foreach ($grn->goodReceiptNoteDetails as $detail) {
                $purchase_detail = $detail->purchaseDetail;
                $already_received = (float) ($purchase_detail->received_quantity ?? 0);
                $ordered_quantity = (float) ($purchase_detail->ordered_quantity ?? 0);

                $data['details'][] = [
                    'good_receipt_note_detail_id' => $detail->good_receipt_note_detail_id,
                    'purchase_detail_id'          => $detail->purchase_detail_id,
                    'product_id'                  => $detail->product_id,
                    'product_name'                => $detail->product->name ?? '',
                    'product_variation_id'        => $detail->product_variation_id,
                    'product_variation_name'      => $detail->productVariation->name ?? '',
                    'ordered_quantity'            => $ordered_quantity,
                    'already_received_quantity'   => $already_received,
                    'remaining_quantity'          => $ordered_quantity - $already_received,
                    'received_quantity'           => $detail->received_quantity,
                    'unit_id'                     => $detail->unit_id,
                    'unit_name'                   => $detail->unit->name ?? 'N/A',
                    'conversion_factor'           => $detail->conversion_factor,
                    'unit_price'                  => $detail->unit_price,
                    'total'                       => $detail->total,
                ];
            }

            return $data;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Fresh receivable lines for a Purchase, used to seed a new GRN's product
     * rows before any GRN exists yet (each line's remaining quantity already
     * accounts for everything posted by prior approved GRNs).
     */
    public function getReceivableLines($purchase_id)
    {
        $purchase = Purchase::with([
            'supplier',
            'warehouse',
            'purchaseDetails',
            'purchaseDetails.product',
            'purchaseDetails.productVariation',
            'purchaseDetails.unit',
        ])->findOrFail($purchase_id);

        $lines = [];

        foreach ($purchase->purchaseDetails as $detail) {
            $already_received = (float) ($detail->received_quantity ?? 0);
            $ordered_quantity = (float) ($detail->ordered_quantity ?? 0);
            $remaining = $ordered_quantity - $already_received;

            if ($remaining <= 0) {
                continue;
            }

            $lines[] = [
                'purchase_detail_id'        => $detail->purchase_detail_id,
                'product_id'                 => $detail->product_id,
                'product_name'               => $detail->product->name ?? '',
                'product_variation_id'       => $detail->product_variation_id,
                'product_variation_name'     => $detail->productVariation->name ?? '',
                'ordered_quantity'           => $ordered_quantity,
                'already_received_quantity'  => $already_received,
                'remaining_quantity'         => $remaining,
                'unit_id'                    => $detail->unit_id,
                'unit_name'                  => $detail->unit->name ?? 'N/A',
                'conversion_factor'          => $detail->conversion_factor,
                'unit_price'                 => $detail->unit_price,
            ];
        }

        return [
            'header' => [
                'purchase_id'     => $purchase->purchase_id,
                'supplier_id'     => $purchase->supplier_id,
                'supplier_name'   => $purchase->supplier->name ?? '',
                'warehouse_id'    => $purchase->warehouse_id,
                'warehouse_name'  => $purchase->warehouse->name ?? '',
            ],
            'lines' => $lines,
        ];
    }

    public function status($obj)
    {
        DB::beginTransaction();

        try {
            $grn = $this->model_grn->getModel()::with($this->with)->findOrFail($obj['good_receipt_note_id']);
            $old_status = $grn->status;
            $new_status = $obj['status'];

            $grn->update([
                'status'       => $new_status,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            if ($new_status === Status::APPROVED && $old_status !== Status::APPROVED) {
                $this->applyGrnApproval($grn);
            } elseif ($old_status === Status::APPROVED && $new_status !== Status::APPROVED) {
                $this->reverseGrnApproval($grn);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $grn;
    }

    public function delete($good_receipt_note_id)
    {
        DB::beginTransaction();

        try {
            $grn = $this->model_grn->getModel()::with($this->with)->findOrFail($good_receipt_note_id);

            if ($grn->status === Status::APPROVED) {
                $this->reverseGrnApproval($grn);
            }

            $grn->update([
                'is_deleted'   => 1,
                'status'       => Status::CANCELLED,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Auto-post a Purchase Voucher and stock-in transactions when a GRN is
     * approved, and roll the received quantity forward onto the originating
     * Purchase's lines. Idempotent: a no-op if an active voucher already
     * exists for this GRN.
     */
    protected function applyGrnApproval(GoodReceiptNote $grn)
    {
        $existing = JournalEntry::where('source_type', JournalSourceTypes::GOODS_RECEIPT)
            ->where('source_id', $grn->good_receipt_note_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $grn->business_id)->first();

        if (!$accounting_setting || !$accounting_setting->enable_accounting || empty($accounting_setting->default_purchase_account_id)) {
            throw new Exception('Purchase Account is not configured in Accounting Settings. Please configure it before approving GRNs.');
        }

        if (empty($grn->supplier) || empty($grn->supplier->account_id)) {
            throw new Exception('The supplier does not have a linked Chart of Account. Please configure it before approving GRNs.');
        }

        $journal = Journal::where('short', 'PV')->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "Purchase Voucher" journal category found. Please configure it before approving GRNs.');
        }

        $entry_no = generateJVNum($journal->journal_id);

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $grn->business_id,
            'branch_id'        => $grn->branch_id,
            'entry_no'         => $entry_no,
            'reference_no'     => $grn->good_receipt_note_no,
            'entry_date'       => now(),
            'description'      => 'Auto-generated payment voucher for approved GRN ' . $grn->good_receipt_note_no,
            'source_type'      => JournalSourceTypes::GOODS_RECEIPT,
            'source_id'        => $grn->good_receipt_note_id,
            'status'           => 'posted',
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        $amount = $grn->total;

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $accounting_setting->default_purchase_account_id,
            'debit'                   => $amount,
            'credit'                  => 0,
            'supplier_id'             => $grn->supplier_id,
            'description'             => 'GRN - ' . $grn->good_receipt_note_no,
        ]);

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $grn->supplier->account_id,
            'debit'                   => 0,
            'credit'                  => $amount,
            'supplier_id'             => $grn->supplier_id,
            'description'             => 'GRN - ' . $grn->good_receipt_note_no,
        ]);

        foreach ($grn->goodReceiptNoteDetails as $detail) {
            $conversion_factor = $detail->conversion_factor > 0 ? $detail->conversion_factor : 1;
            $quantity = $detail->received_quantity;
            $base_quantity = $quantity * $conversion_factor;

            if ($base_quantity <= 0) {
                continue;
            }

            $purchase_detail = PurchaseDetail::where('purchase_detail_id', $detail->purchase_detail_id)->first();

            if ($purchase_detail) {
                $new_received = (float) ($purchase_detail->received_quantity ?? 0) + $quantity;

                if ($new_received > (float) $purchase_detail->ordered_quantity) {
                    throw new Exception('Approving this GRN would receive more than the ordered quantity for "' . ($purchase_detail->product->name ?? 'a product') . '". Please adjust the GRN.');
                }

                $purchase_detail->update([
                    'received_quantity' => $new_received,
                    'updatedby_id'      => Auth::id(),
                    'date_updated'      => now(),
                ]);
            }

            $line_cost = $detail->unit_price * $base_quantity;

            $stock = ProductVariationStock::where('business_id', $grn->business_id)
                ->where('warehouse_id', $grn->warehouse_id)
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
                    'business_id'                => $grn->business_id,
                    'warehouse_id'               => $grn->warehouse_id,
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
                'business_id'                             => $grn->business_id,
                'product_id'                              => $detail->product_id,
                'product_variation_id'                    => $detail->product_variation_id,
                'warehouse_id'                             => $grn->warehouse_id,
                'unit_id'                                  => $detail->unit_id,
                'product_variation_unit_conversion_id'     => $detail->product_variation_unit_conversion_id,
                'conversion_factor'                        => $conversion_factor,
                'quantity'                                 => $quantity,
                'base_quantity'                            => $base_quantity,
                'unit_price'                               => $detail->unit_price,
                'total_price'                              => $line_cost,
                'quantity_after'                           => $new_qty,
                'avg_price_after'                          => $new_avg,
                'reference_id'                              => $grn->good_receipt_note_id,
                'reference_type'                            => ReferenceType::GRN,
                'remarks'                                   => 'Auto-created on approval of GRN ' . $grn->good_receipt_note_no,
                'createdby_id'                              => Auth::id(),
                'date_created'                              => now(),
            ]);
        }

        app(PurchaseService::class)->syncPurchaseCompletionStatus($grn->purchase_id);
    }

    /**
     * Reverse the Purchase Voucher and stock effects created when a GRN was
     * approved, and roll the received quantity back off the originating
     * Purchase's lines. Idempotent: a no-op if nothing active remains to
     * reverse.
     */
    protected function reverseGrnApproval(GoodReceiptNote $grn)
    {
        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::GOODS_RECEIPT)
            ->where('source_id', $grn->good_receipt_note_id)
            ->where('is_deleted', 0)
            ->first();

        if ($journal_entry) {
            $journal_entry->update([
                'is_deleted'   => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);
        }

        foreach ($grn->goodReceiptNoteDetails as $detail) {
            $purchase_detail = PurchaseDetail::where('purchase_detail_id', $detail->purchase_detail_id)->first();

            if ($purchase_detail) {
                $rolled_back = max(0, (float) ($purchase_detail->received_quantity ?? 0) - (float) $detail->received_quantity);

                $purchase_detail->update([
                    'received_quantity' => $rolled_back,
                    'updatedby_id'      => Auth::id(),
                    'date_updated'      => now(),
                ]);
            }
        }

        $stock_transactions = ProductVariationStockTransaction::where('reference_type', ReferenceType::GRN)
            ->where('reference_id', $grn->good_receipt_note_id)
            ->where('is_deleted', 0)
            ->get();

        if ($stock_transactions->isNotEmpty()) {
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

        app(PurchaseService::class)->syncPurchaseCompletionStatus($grn->purchase_id);
    }

    public function getByBusiness($business_id)
    {
        return $this->model_grn->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
