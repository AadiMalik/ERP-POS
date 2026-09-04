<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\JournalSourceTypes;
use App\Enums\LossType;
use App\Enums\ReferenceType;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\AccountingSetting;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\ProductVariationBatch;
use App\Models\ProductVariationStock;
use App\Models\ProductVariationStockTransaction;
use App\Models\WasteDamageExpiry;
use App\Models\WasteDamageExpiryDetail;
use App\Models\Warehouse;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class WasteDamageExpiryService
{
    use Auditable;

    protected $model_waste_damage_expiry;
    protected $model_waste_damage_expiry_details;
    protected $with = [
        'business',
        'branch',
        'warehouse',
        'createdby',
        'approvedby',
        'details',
        'details.product',
        'details.productVariation',
        'details.unit',
        'details.productVariationBatch',
        'details.lossReason',
    ];

    public function __construct()
    {
        $this->model_waste_damage_expiry = new Repository(new WasteDamageExpiry());
        $this->model_waste_damage_expiry_details = new Repository(new WasteDamageExpiryDetail());
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
        if (isset($obj['warehouse_id']) && $obj['warehouse_id'] != 0 && $obj['warehouse_id'] != "") {
            $wh[] = ['warehouse_id', $obj['warehouse_id']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['transaction_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['transaction_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::INVENTORYMANAGER,
            RoleNames::BRANCHADMIN,
            RoleNames::POSMANAGER,
        ];

        $datatable = $this->model_waste_damage_expiry->getModel()::with($this->with)
            ->withCount('details as total_products')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('transaction_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('transaction_date', function ($item) {
                return !empty($item->transaction_date) ? localDate($item->transaction_date) : 'N/A';
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
            ->addColumn('total_value', function ($item) {
                return currency($item->total_value ?? 0);
            })
            ->addColumn('status', function ($item) {
                $statuses = [
                    Status::PENDING   => ucfirst(Status::PENDING),
                    Status::APPROVED  => ucfirst(Status::APPROVED),
                    Status::CANCELLED => ucfirst(Status::CANCELLED),
                ];

                $disabled = $item->status === Status::CANCELLED ? 'disabled' : '';

                $html = "<select class='form-select form-select-sm change-status' data-id='{$item->waste_damage_expiry_id}' {$disabled}>";
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
                        href='" . route('waste-damage-expiry.edit', $item->waste_damage_expiry_id) . "'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='Only pending records can be edited'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                $printButton = "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('waste-damage-expiry.print', $item->waste_damage_expiry_id) . "' title='Print'>
                    <i class='fa fa-print'></i>
                    </a>";

                $deleteButton = $item->status !== Status::CANCELLED
                    ? "<a class='btn btn-icon btn-outline-danger'
                    id='deleteWasteDamageExpiry'
                    data-id='{$item->waste_damage_expiry_id}'>
                    <i class='fa fa-trash'></i>
                    </a>"
                    : '';

                return $editButton . $printButton . $deleteButton;
            })
            ->rawColumns(['business', 'branch', 'warehouse', 'total_products', 'total_value', 'status', 'action'])
            ->make(true);
    }

    /**
     * Active batches for a product/variation in a warehouse, for the create
     * form's batch dropdown (manual selection, not FEFO - the user is
     * recording a specific known batch as wasted/damaged/expired).
     */
    public function getBatches($warehouse_id, $product_variation_id)
    {
        return ProductVariationBatch::where('warehouse_id', $warehouse_id)
            ->where('product_variation_id', $product_variation_id)
            ->where('status', Status::ACTIVE)
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date')
            ->get(['product_variation_batch_id', 'batch_no', 'quantity', 'avg_price', 'expiry_date']);
    }

    /**
     * Current aggregate stock for a product/variation in a warehouse, so the
     * create form can show the available quantity before the user enters how
     * much to write off.
     */
    public function getStock($warehouse_id, $product_variation_id)
    {
        $stock = ProductVariationStock::where('warehouse_id', $warehouse_id)
            ->where('product_variation_id', $product_variation_id)
            ->first();

        return [
            'quantity'           => (float) ($stock->quantity ?? 0),
            'reserved_quantity'  => (float) ($stock->reserved_quantity ?? 0),
            'available_quantity' => (float) ($stock->quantity ?? 0) - (float) ($stock->reserved_quantity ?? 0),
            'avg_price'          => (float) ($stock->avg_price ?? 0),
        ];
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {
            $warehouse = Warehouse::find($obj['warehouse_id'] ?? null);

            if (!$warehouse) {
                throw new Exception('The selected warehouse was not found.');
            }

            $business_id = $warehouse->business_id;
            $branch_id = $warehouse->branch_id;

            $is_update = !empty($obj['waste_damage_expiry_id']);
            $old_snapshot = null;

            if ($is_update) {
                $wde = $this->model_waste_damage_expiry->getModel()::findOrFail($obj['waste_damage_expiry_id']);

                if ($wde->status !== Status::PENDING) {
                    throw new Exception('Only pending records can be updated.');
                }

                $old_snapshot = [
                    'warehouse_id'     => $wde->warehouse_id,
                    'transaction_date' => $wde->transaction_date,
                    'total_quantity'   => $wde->total_quantity,
                    'total_value'      => $wde->total_value,
                ];

                $wde->update([
                    'business_id'      => $business_id,
                    'branch_id'        => $branch_id,
                    'warehouse_id'     => $warehouse->warehouse_id,
                    'transaction_date' => $obj['transaction_date'],
                    'reference'        => $obj['reference'] ?? null,
                    'notes'            => $obj['notes'] ?? null,
                    'updatedby_id'     => Auth::id(),
                    'date_updated'     => now(),
                ]);

                WasteDamageExpiryDetail::where('waste_damage_expiry_id', $wde->waste_damage_expiry_id)->delete();
            } else {
                $wde = $this->model_waste_damage_expiry->create([
                    'waste_damage_expiry_id' => generateUuid(),
                    'business_id'            => $business_id,
                    'branch_id'              => $branch_id,
                    'warehouse_id'           => $warehouse->warehouse_id,
                    'reference_no'           => $obj['reference_no'],
                    'transaction_date'       => $obj['transaction_date'],
                    'reference'              => $obj['reference'] ?? null,
                    'notes'                  => $obj['notes'] ?? null,
                    'status'                 => Status::PENDING,
                    'createdby_id'           => Auth::id(),
                    'date_created'           => now(),
                ]);
            }

            $total_quantity = 0;
            $total_value = 0;
            $has_line = false;

            foreach ($obj['lines'] as $line) {
                if (empty($line['product_id']) || empty($line['product_variation_id'])) {
                    continue;
                }

                $quantity = (float) ($line['quantity'] ?? 0);

                if ($quantity <= 0) {
                    continue;
                }

                $has_line = true;

                $stock = ProductVariationStock::where('business_id', $business_id)
                    ->where('warehouse_id', $warehouse->warehouse_id)
                    ->where('product_id', $line['product_id'])
                    ->where('product_variation_id', $line['product_variation_id'])
                    ->first();

                $available = (float) ($stock->quantity ?? 0) - (float) ($stock->reserved_quantity ?? 0);

                if ($quantity > $available) {
                    throw new Exception('Requested quantity (' . $quantity . ') exceeds available stock (' . $available . ') for the selected product/variation.');
                }

                $batch = null;

                if (!empty($line['product_variation_batch_id'])) {
                    $batch = ProductVariationBatch::where('product_variation_batch_id', $line['product_variation_batch_id'])
                        ->where('warehouse_id', $warehouse->warehouse_id)
                        ->first();

                    if (!$batch) {
                        throw new Exception('The selected batch was not found in this warehouse.');
                    }

                    if ($quantity > (float) $batch->quantity) {
                        throw new Exception('Requested quantity (' . $quantity . ') exceeds available batch quantity (' . $batch->quantity . ') for batch ' . $batch->batch_no . '.');
                    }
                }

                $unit_cost = (float) ($batch->avg_price ?? $stock->avg_price ?? 0);
                $value = $quantity * $unit_cost;

                $total_quantity += $quantity;
                $total_value += $value;

                $this->model_waste_damage_expiry_details->create([
                    'waste_damage_expiry_detail_id' => generateUuid(),
                    'waste_damage_expiry_id'        => $wde->waste_damage_expiry_id,
                    'product_id'                    => $line['product_id'],
                    'product_variation_id'          => $line['product_variation_id'],
                    'unit_id'                        => $line['unit_id'] ?? null,
                    'product_variation_batch_id'    => $batch->product_variation_batch_id ?? null,
                    'batch_no'                       => $batch->batch_no ?? ($line['batch_no'] ?? null),
                    'expiry_date'                    => $batch->expiry_date ?? ($line['expiry_date'] ?? null),
                    'quantity'                       => $quantity,
                    'unit_cost'                      => $unit_cost,
                    'value'                          => $value,
                    'loss_type'                      => $line['loss_type'] ?? LossType::OTHER,
                    'loss_reason_id'                 => $line['loss_reason_id'] ?? null,
                    'notes'                          => $line['notes'] ?? null,
                    'createdby_id'                   => Auth::id(),
                    'date_created'                   => now(),
                ]);
            }

            if (!$has_line) {
                throw new Exception('Please add at least one product with a quantity greater than zero.');
            }

            $wde->update([
                'total_quantity' => $total_quantity,
                'total_value'    => $total_value,
            ]);

            DB::commit();

            $this->logActivity(
                'waste-damage-expiry',
                $wde->waste_damage_expiry_id,
                $is_update ? 'updated' : 'created',
                $old_snapshot,
                [
                    'warehouse_id'     => $wde->warehouse_id,
                    'transaction_date' => $wde->transaction_date,
                    'total_quantity'   => $total_quantity,
                    'total_value'      => $total_value,
                ],
                null,
                $business_id,
                $branch_id
            );

            return $wde;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function getById($waste_damage_expiry_id)
    {
        return $this->model_waste_damage_expiry->with($this->with)->find($waste_damage_expiry_id);
    }

    public function getDetails($waste_damage_expiry_id)
    {
        $wde = $this->model_waste_damage_expiry->getModel()::with($this->with)->findOrFail($waste_damage_expiry_id);

        $data = [
            'header' => [
                'waste_damage_expiry_id' => $wde->waste_damage_expiry_id,
                'warehouse_id'           => $wde->warehouse_id,
                'branch_id'              => $wde->branch_id,
                'reference_no'           => $wde->reference_no,
                'transaction_date'       => $wde->transaction_date,
                'reference'              => $wde->reference,
                'notes'                  => $wde->notes,
                'total_quantity'         => $wde->total_quantity,
                'total_value'            => $wde->total_value,
                'status'                 => $wde->status,
            ],
            'details' => [],
        ];

        foreach ($wde->details as $detail) {
            $data['details'][] = [
                'waste_damage_expiry_detail_id' => $detail->waste_damage_expiry_detail_id,
                'product_id'                    => $detail->product_id,
                'product_name'                  => $detail->product->name ?? '',
                'product_variation_id'          => $detail->product_variation_id,
                'variation_name'                => $detail->productVariation->name ?? '',
                'unit_id'                        => $detail->unit_id,
                'unit_name'                      => $detail->unit->name ?? 'N/A',
                'product_variation_batch_id'    => $detail->product_variation_batch_id,
                'batch_no'                       => $detail->batch_no,
                'expiry_date'                    => $detail->expiry_date,
                'quantity'                       => $detail->quantity,
                'unit_cost'                      => $detail->unit_cost,
                'value'                          => $detail->value,
                'loss_type'                      => $detail->loss_type,
                'loss_reason_id'                 => $detail->loss_reason_id,
                'loss_reason_name'               => $detail->lossReason->name ?? null,
                'notes'                          => $detail->notes,
            ];
        }

        return $data;
    }

    public function status($obj)
    {
        DB::beginTransaction();

        try {
            $wde = $this->model_waste_damage_expiry->getModel()::with($this->with)->findOrFail($obj['waste_damage_expiry_id']);
            $old_status = $wde->status;
            $new_status = $obj['status'];

            $allowed_transitions = [
                Status::PENDING   => [Status::APPROVED, Status::CANCELLED],
                Status::APPROVED  => [Status::CANCELLED],
                Status::CANCELLED => [],
            ];

            if (!in_array($new_status, $allowed_transitions[$old_status] ?? [], true)) {
                throw new Exception("This record cannot be changed from '{$old_status}' to '{$new_status}'.");
            }

            $update = [
                'status'       => $new_status,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ];

            if ($new_status === Status::APPROVED) {
                $update['approvedby_id'] = Auth::id();
                $update['date_approved'] = now();
            }

            $wde->update($update);

            if ($new_status === Status::APPROVED) {
                $this->applyPosting($wde);
            } elseif ($old_status === Status::APPROVED && $new_status === Status::CANCELLED) {
                $this->reversePosting($wde);
            }

            DB::commit();

            $this->logActivity(
                'waste-damage-expiry',
                $wde->waste_damage_expiry_id,
                $new_status === Status::APPROVED ? 'approved' : 'status_changed',
                ['status' => $old_status],
                ['status' => $new_status]
            );
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $wde;
    }

    public function delete($waste_damage_expiry_id)
    {
        DB::beginTransaction();

        try {
            $wde = $this->model_waste_damage_expiry->getModel()::with($this->with)->findOrFail($waste_damage_expiry_id);
            $old_status = $wde->status;

            if ($old_status === Status::APPROVED) {
                $this->reversePosting($wde);
            }

            $wde->update([
                'is_deleted'   => 1,
                'status'       => Status::CANCELLED,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            DB::commit();

            $this->logActivity(
                'waste-damage-expiry',
                $wde->waste_damage_expiry_id,
                'deleted',
                [
                    'status'         => $old_status,
                    'total_quantity' => $wde->total_quantity,
                    'total_value'    => $wde->total_value,
                ],
                null,
                null,
                $wde->business_id,
                $wde->branch_id
            );

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Write off the approved quantities from live stock/batches and post the
     * loss transactions + (if the value is non-zero and accounting requires
     * it) the Stock Loss Voucher JV. Idempotent: a no-op if stock
     * transactions for this record already exist.
     */
    protected function applyPosting(WasteDamageExpiry $wde)
    {
        $existing = ProductVariationStockTransaction::where('reference_id', $wde->waste_damage_expiry_id)
            ->whereIn('reference_type', [ReferenceType::DAMAGE_NOTE, ReferenceType::EXPIRY_NOTE, ReferenceType::WASTAGE_NOTE])
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $stock_service = app(ProductVariationStockService::class);
        $total_value = 0;

        foreach ($wde->details as $detail) {
            $stock = ProductVariationStock::where('business_id', $wde->business_id)
                ->where('warehouse_id', $wde->warehouse_id)
                ->where('product_id', $detail->product_id)
                ->where('product_variation_id', $detail->product_variation_id)
                ->lockForUpdate()
                ->first();

            $available = (float) ($stock->quantity ?? 0) - (float) ($stock->reserved_quantity ?? 0);

            if ((float) $detail->quantity > $available) {
                throw new Exception('Insufficient live stock to approve this write-off for ' . ($detail->product->name ?? 'a product') . '. Available: ' . $available . ', requested: ' . $detail->quantity . '.');
            }

            $batch = null;

            if ($detail->product_variation_batch_id) {
                $batch = ProductVariationBatch::lockForUpdate()->find($detail->product_variation_batch_id);

                if (!$batch || (float) $detail->quantity > (float) $batch->quantity) {
                    throw new Exception('Insufficient batch quantity to approve this write-off for batch ' . ($detail->batch_no ?? '-') . '.');
                }
            }

            $unit_cost = (float) ($batch->avg_price ?? $stock->avg_price ?? 0);
            $line_value = (float) $detail->quantity * $unit_cost;
            $total_value += $line_value;

            // A loss removes quantity but never changes the moving-average
            // cost - decrement the live locked quantity directly (same as
            // StockTakingService's shortage posting), never replay the full
            // ledger history here: recomputeLedger() is for cleaning up
            // after a reversal, not for normal forward posting, since it
            // would silently drop any pre-existing balance that didn't
            // arrive via a stock transaction (e.g. a seeded/imported balance).
            $new_quantity = (float) ($stock->quantity ?? 0) - (float) $detail->quantity;

            $transaction_type = LossType::toTransactionType($detail->loss_type);
            $reference_type = LossType::toReferenceType($detail->loss_type);
            $loss_label = LossType::getOptions()[$detail->loss_type] ?? $detail->loss_type;

            ProductVariationStockTransaction::create([
                'product_variation_stock_transaction_id' => generateUuid(),
                'transaction_date'                        => now(),
                'transaction_type'                         => $transaction_type,
                'business_id'                              => $wde->business_id,
                'product_id'                               => $detail->product_id,
                'product_variation_id'                     => $detail->product_variation_id,
                'warehouse_id'                              => $wde->warehouse_id,
                'unit_id'                                   => $detail->unit_id,
                'conversion_factor'                         => 1,
                'quantity'                                  => $detail->quantity,
                'base_quantity'                             => $detail->quantity,
                'unit_price'                                => $unit_cost,
                'total_price'                               => $line_value,
                'quantity_after'                            => $new_quantity,
                'avg_price_after'                           => (float) ($stock->avg_price ?? 0),
                'reference_id'                               => $wde->waste_damage_expiry_id,
                'reference_type'                             => $reference_type,
                'remarks'                                    => 'Loss (' . $loss_label . ') via ' . $wde->reference_no
                    . (!empty($detail->lossReason->name) ? ' - ' . $detail->lossReason->name : ''),
                'product_variation_batch_id'                 => $batch->product_variation_batch_id ?? null,
                'createdby_id'                               => Auth::id(),
                'date_created'                               => now(),
            ]);

            $stock->update(['quantity' => $new_quantity]);

            if ($batch) {
                $stock_service->adjustBatchQuantity($batch->product_variation_batch_id, -1 * (float) $detail->quantity);
            }

            $detail->update([
                'unit_cost' => $unit_cost,
                'value'     => $line_value,
            ]);
        }

        $wde->update(['total_value' => $total_value]);

        if ($total_value <= 0) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $wde->business_id)->first();

        if (
            !$accounting_setting
            || !$accounting_setting->enable_accounting
            || empty($accounting_setting->default_inventory_account_id)
            || empty($accounting_setting->default_stock_adjustment_account_id)
        ) {
            throw new Exception('Inventory Account / Stock Adjustment Account is not configured in Accounting Settings. Please configure it before approving this Waste/Damage/Expiry record.');
        }

        app(AccountingPeriodService::class)->assertPostable($wde->business_id, now());

        $journal = Journal::where('short', 'SLV')->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "Stock Loss Voucher" journal category found. Please configure it before approving.');
        }

        $entry_no = generateJVNum($journal->journal_id);

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $wde->business_id,
            'branch_id'        => $wde->branch_id,
            'entry_no'         => $entry_no,
            'reference_no'     => $wde->reference_no,
            'entry_date'       => now(),
            'description'      => 'Auto-generated stock loss voucher for approved Waste/Damage/Expiry ' . $wde->reference_no,
            'source_type'      => JournalSourceTypes::STOCK_LOSS,
            'source_id'        => $wde->waste_damage_expiry_id,
            'status'           => 'posted',
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $accounting_setting->default_stock_adjustment_account_id,
            'debit'                   => $total_value,
            'credit'                  => 0,
            'description'             => 'Stock Loss - ' . $wde->reference_no,
        ]);

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $accounting_setting->default_inventory_account_id,
            'debit'                   => 0,
            'credit'                  => $total_value,
            'description'             => 'Stock Loss - ' . $wde->reference_no,
        ]);

        JournalEntryService::assertBalanced($journal_entry->journal_entry_id);
    }

    /**
     * Reverse the Stock Loss Voucher and stock/batch effects created when a
     * Waste/Damage/Expiry record was approved. Idempotent: a no-op if
     * nothing active remains to reverse.
     */
    protected function reversePosting(WasteDamageExpiry $wde)
    {
        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::STOCK_LOSS)
            ->where('source_id', $wde->waste_damage_expiry_id)
            ->where('is_deleted', 0)
            ->first();

        if ($journal_entry) {
            app(AccountingPeriodService::class)->assertPostable($journal_entry->business_id, $journal_entry->entry_date);

            $journal_entry->update([
                'is_deleted'   => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);
        }

        $stock_transactions = ProductVariationStockTransaction::where('reference_id', $wde->waste_damage_expiry_id)
            ->whereIn('reference_type', [ReferenceType::DAMAGE_NOTE, ReferenceType::EXPIRY_NOTE, ReferenceType::WASTAGE_NOTE])
            ->where('is_deleted', 0)
            ->get();

        if ($stock_transactions->isEmpty()) {
            return;
        }

        app(ProductVariationStockService::class)->reverseStockTransactions($stock_transactions);
    }

    public function getByBusiness($business_id)
    {
        return $this->model_waste_damage_expiry->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
