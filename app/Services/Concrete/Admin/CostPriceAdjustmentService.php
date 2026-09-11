<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\JournalSourceTypes;
use App\Enums\ReferenceType;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Models\AccountingSetting;
use App\Models\CostPriceAdjustment;
use App\Models\CostPriceAdjustmentBatch;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\ProductVariation;
use App\Models\ProductVariationBatch;
use App\Models\ProductVariationStock;
use App\Models\ProductVariationStockTransaction;
use App\Models\Warehouse;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class CostPriceAdjustmentService
{
    use Auditable;

    protected $model_cost_price_adjustment;
    protected $with = [
        'business',
        'branch',
        'warehouse',
        'product',
        'productVariation',
        'createdby',
        'updatedby',
        'approvedby',
        'batches',
    ];

    public function __construct()
    {
        $this->model_cost_price_adjustment = new Repository(new CostPriceAdjustment());
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
        if (isset($obj['product_id']) && $obj['product_id'] != 0 && $obj['product_id'] != "") {
            $wh[] = ['product_id', $obj['product_id']];
        }
        if (isset($obj['product_variation_id']) && $obj['product_variation_id'] != 0 && $obj['product_variation_id'] != "") {
            $wh[] = ['product_variation_id', $obj['product_variation_id']];
        }
        if (isset($obj['createdby_id']) && $obj['createdby_id'] != 0 && $obj['createdby_id'] != "") {
            $wh[] = ['createdby_id', $obj['createdby_id']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (isset($obj['reference_no']) && $obj['reference_no'] != "") {
            $wh[] = ['reference_no', 'like', '%' . $obj['reference_no'] . '%'];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['adjustment_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['adjustment_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::INVENTORYMANAGER,
            RoleNames::BRANCHADMIN,
        ];

        $datatable = $this->model_cost_price_adjustment->getModel()::with($this->with)
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('adjustment_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('adjustment_date', function ($item) {
                return !empty($item->adjustment_date) ? businessDate($item->adjustment_date) : 'N/A';
            })
            ->addColumn('product', function ($item) {
                return $item->product->name ?? '';
            })
            ->addColumn('variation', function ($item) {
                return $item->productVariation->name ?? '';
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
            ->addColumn('previous_cost_price', function ($item) {
                return currency($item->previous_cost_price ?? 0);
            })
            ->addColumn('new_cost_price', function ($item) {
                return currency($item->new_cost_price ?? 0);
            })
            ->addColumn('difference_per_unit', function ($item) {
                return currency($item->difference_per_unit ?? 0);
            })
            ->addColumn('total_adjustment_amount', function ($item) {
                return currency($item->total_adjustment_amount ?? 0);
            })
            ->addColumn('created_by', function ($item) {
                return $item->createdby->name ?? '';
            })
            ->addColumn('status', function ($item) {
                $statuses = [
                    Status::PENDING   => ucfirst(Status::PENDING),
                    Status::APPROVED  => ucfirst(Status::APPROVED),
                    Status::CANCELLED => ucfirst(Status::CANCELLED),
                ];

                $disabled = $item->status === Status::CANCELLED ? 'disabled' : '';

                $html = "<select class='form-select form-select-sm change-status' data-id='{$item->cost_price_adjustment_id}' {$disabled}>";
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
                        href='" . route('cost-price-adjustment.edit', $item->cost_price_adjustment_id) . "'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='" . e(__('cost_price_adjustment.only_pending_can_be_edited')) . "'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                $viewJvButton = $item->status === Status::APPROVED
                    ? "<button type='button' class='btn btn-icon btn-outline-dark mr-2 view-jv-btn'
                        data-source-type='" . JournalSourceTypes::INVENTORY_COST_ADJUSTMENT . "'
                        data-source-id='{$item->cost_price_adjustment_id}'
                        title='" . e(__('cost_price_adjustment.journal_voucher')) . "'>
                        <i class='fa fa-book'></i>
                        </button>"
                    : '';

                $printButton = "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('cost-price-adjustment.print', $item->cost_price_adjustment_id) . "' title='" . e(__('common.print')) . "'>
                    <i class='fa fa-print'></i>
                    </a>";

                $deleteButton = $item->status !== Status::CANCELLED
                    ? "<a class='btn btn-icon btn-outline-danger'
                    id='deleteCostPriceAdjustment'
                    data-id='{$item->cost_price_adjustment_id}'>
                    <i class='fa fa-trash'></i>
                    </a>"
                    : '';

                return $editButton . $viewJvButton . $printButton . $deleteButton;
            })
            ->rawColumns(['business', 'branch', 'warehouse', 'status', 'action'])
            ->make(true);
    }

    /**
     * Current aggregate stock for a product/variation in a warehouse, so the
     * create form can show the live "Previous Cost Price" before the user
     * enters a new one.
     */
    public function getStock($warehouse_id, $product_variation_id)
    {
        $stock = ProductVariationStock::where('warehouse_id', $warehouse_id)
            ->where('product_variation_id', $product_variation_id)
            ->first();

        return [
            'quantity'  => (float) ($stock->quantity ?? 0),
            'avg_price' => (float) ($stock->avg_price ?? 0),
        ];
    }

    /**
     * Active batches for a product/variation in a warehouse, for the create
     * form's read-only batch-breakdown preview when the variation is
     * batch/expiry-tracked - the adjustment will revalue every one of these
     * to the new unit cost.
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
     * Create/update a draft (pending) Cost Price Adjustment. previous_cost_price,
     * quantity_on_hand, difference_per_unit and total_adjustment_amount are
     * always (re)computed here from the LIVE ProductVariationStock row - never
     * accepted from the request - so the draft always reflects current reality
     * up to the moment it is saved. These are still only a preview: the
     * authoritative figures actually applied are recomputed again (under a
     * row lock) in applyPosting() at approval time.
     */
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

            $variation = ProductVariation::where('product_variation_id', $obj['product_variation_id'] ?? null)
                ->where('product_id', $obj['product_id'] ?? null)
                ->where('is_deleted', 0)
                ->first();

            if (!$variation) {
                throw new Exception('The selected product variation was not found for the selected product.');
            }

            $new_cost_price = (float) ($obj['new_cost_price'] ?? 0);

            if ($new_cost_price <= 0) {
                throw new Exception('The new cost price must be greater than zero.');
            }

            $is_update = !empty($obj['cost_price_adjustment_id']);
            $old_snapshot = null;
            $cpa = null;

            if ($is_update) {
                $cpa = $this->model_cost_price_adjustment->getModel()::findOrFail($obj['cost_price_adjustment_id']);

                if ($cpa->status !== Status::PENDING) {
                    throw new Exception('Only pending records can be updated.');
                }

                $old_snapshot = [
                    'warehouse_id'   => $cpa->warehouse_id,
                    'new_cost_price' => $cpa->new_cost_price,
                    'reason'         => $cpa->reason,
                ];
            }

            $duplicate_query = $this->model_cost_price_adjustment->getModel()::where('warehouse_id', $warehouse->warehouse_id)
                ->where('product_variation_id', $obj['product_variation_id'])
                ->where('status', Status::PENDING)
                ->where('is_deleted', 0);

            if ($is_update) {
                $duplicate_query->where('cost_price_adjustment_id', '!=', $cpa->cost_price_adjustment_id);
            }

            if ($duplicate_query->exists()) {
                throw new Exception('A pending Cost Price Adjustment already exists for this product variation in this warehouse. Please approve or cancel it before creating another.');
            }

            $stock = ProductVariationStock::where('business_id', $business_id)
                ->where('warehouse_id', $warehouse->warehouse_id)
                ->where('product_id', $obj['product_id'])
                ->where('product_variation_id', $obj['product_variation_id'])
                ->first();

            $previous_cost_price = (float) ($stock->avg_price ?? 0);
            $quantity_on_hand = (float) ($stock->quantity ?? 0);
            $difference_per_unit = round($new_cost_price - $previous_cost_price, 4);
            $total_adjustment_amount = round($quantity_on_hand * $difference_per_unit, 4);

            $data = [
                'business_id'             => $business_id,
                'branch_id'               => $branch_id,
                'warehouse_id'            => $warehouse->warehouse_id,
                'product_id'              => $obj['product_id'],
                'product_variation_id'    => $obj['product_variation_id'],
                'adjustment_date'         => $obj['adjustment_date'],
                'previous_cost_price'     => $previous_cost_price,
                'new_cost_price'          => $new_cost_price,
                'quantity_on_hand'        => $quantity_on_hand,
                'difference_per_unit'     => $difference_per_unit,
                'total_adjustment_amount' => $total_adjustment_amount,
                'reason'                  => $obj['reason'] ?? null,
                'notes'                   => $obj['notes'] ?? null,
                'reference'               => $obj['reference'] ?? null,
            ];

            if ($is_update) {
                $data['updatedby_id'] = Auth::id();
                $data['date_updated'] = now();
                $cpa->update($data);
            } else {
                $data['cost_price_adjustment_id'] = generateUuid();
                $data['reference_no'] = $obj['reference_no'];
                $data['status'] = Status::PENDING;
                $data['createdby_id'] = Auth::id();
                $data['date_created'] = now();
                $cpa = $this->model_cost_price_adjustment->create($data);
            }

            DB::commit();

            $this->logActivity(
                'cost-price-adjustment',
                $cpa->cost_price_adjustment_id,
                $is_update ? 'updated' : 'created',
                $old_snapshot,
                [
                    'warehouse_id'            => $cpa->warehouse_id,
                    'previous_cost_price'     => $previous_cost_price,
                    'new_cost_price'          => $new_cost_price,
                    'total_adjustment_amount' => $total_adjustment_amount,
                ],
                null,
                $business_id,
                $branch_id
            );

            return $cpa;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function getById($cost_price_adjustment_id)
    {
        return $this->model_cost_price_adjustment->with($this->with)->find($cost_price_adjustment_id);
    }

    public function getDetails($cost_price_adjustment_id)
    {
        $cpa = $this->model_cost_price_adjustment->getModel()::with($this->with)->findOrFail($cost_price_adjustment_id);

        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::INVENTORY_COST_ADJUSTMENT)
            ->where('source_id', $cpa->cost_price_adjustment_id)
            ->where('is_deleted', 0)
            ->first();

        return [
            'cost_price_adjustment_id' => $cpa->cost_price_adjustment_id,
            'reference_no'             => $cpa->reference_no,
            'adjustment_date'          => $cpa->adjustment_date,
            'warehouse_id'             => $cpa->warehouse_id,
            'warehouse_name'           => $cpa->warehouse->name ?? '',
            'product_id'               => $cpa->product_id,
            'product_name'             => $cpa->product->name ?? '',
            'product_variation_id'     => $cpa->product_variation_id,
            'variation_name'           => $cpa->productVariation->name ?? '',
            'previous_cost_price'      => $cpa->previous_cost_price,
            'new_cost_price'           => $cpa->new_cost_price,
            'quantity_on_hand'         => $cpa->quantity_on_hand,
            'difference_per_unit'      => $cpa->difference_per_unit,
            'total_adjustment_amount'  => $cpa->total_adjustment_amount,
            'reason'                   => $cpa->reason,
            'notes'                    => $cpa->notes,
            'reference'                => $cpa->reference,
            'status'                   => $cpa->status,
            'created_by'               => $cpa->createdby->name ?? '',
            'approved_by'              => $cpa->approvedby->name ?? null,
            'date_approved'            => $cpa->date_approved,
            'journal_entry_id'         => $journal_entry->journal_entry_id ?? null,
            'journal_entry_no'         => $journal_entry->entry_no ?? null,
            'batches'                  => $cpa->batches->map(function ($batch) {
                return [
                    'batch_no'                 => $batch->batch_no,
                    'quantity'                 => $batch->quantity,
                    'previous_batch_avg_price' => $batch->previous_batch_avg_price,
                    'new_batch_avg_price'      => $batch->new_batch_avg_price,
                    'batch_adjustment_amount'  => $batch->batch_adjustment_amount,
                ];
            })->values(),
        ];
    }

    public function status($obj)
    {
        DB::beginTransaction();

        try {
            $cpa = $this->model_cost_price_adjustment->getModel()::with($this->with)->findOrFail($obj['cost_price_adjustment_id']);
            $old_status = $cpa->status;
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

            $cpa->update($update);

            if ($new_status === Status::APPROVED) {
                $this->applyPosting($cpa);
            } elseif ($old_status === Status::APPROVED && $new_status === Status::CANCELLED) {
                $this->reversePosting($cpa);
            }

            DB::commit();

            $this->logActivity(
                'cost-price-adjustment',
                $cpa->cost_price_adjustment_id,
                $new_status === Status::APPROVED ? 'approved' : 'status_changed',
                ['status' => $old_status],
                ['status' => $new_status]
            );
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $cpa;
    }

    public function delete($cost_price_adjustment_id)
    {
        DB::beginTransaction();

        try {
            $cpa = $this->model_cost_price_adjustment->getModel()::with($this->with)->findOrFail($cost_price_adjustment_id);
            $old_status = $cpa->status;

            if ($old_status === Status::APPROVED) {
                $this->reversePosting($cpa);
            }

            $cpa->update([
                'is_deleted'   => 1,
                'status'       => Status::CANCELLED,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            DB::commit();

            $this->logActivity(
                'cost-price-adjustment',
                $cpa->cost_price_adjustment_id,
                'deleted',
                [
                    'status'                  => $old_status,
                    'total_adjustment_amount' => $cpa->total_adjustment_amount,
                ],
                null,
                null,
                $cpa->business_id,
                $cpa->branch_id
            );

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Revalue live stock (and every active batch, if the variation is
     * batch/expiry-tracked) to the new cost, write the valuation-only ledger
     * entry/entries (base_quantity = 0 - never a stock movement), and post
     * the Inventory Cost Adjustment Voucher JV if the value delta is
     * non-zero. Idempotent: a no-op if stock transactions for this record
     * already exist.
     */
    protected function applyPosting(CostPriceAdjustment $cpa)
    {
        $existing = ProductVariationStockTransaction::where('reference_id', $cpa->cost_price_adjustment_id)
            ->where('reference_type', ReferenceType::COST_PRICE_ADJUSTMENT)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $stock = ProductVariationStock::where('business_id', $cpa->business_id)
            ->where('warehouse_id', $cpa->warehouse_id)
            ->where('product_id', $cpa->product_id)
            ->where('product_variation_id', $cpa->product_variation_id)
            ->lockForUpdate()
            ->first();

        $live_quantity = (float) ($stock->quantity ?? 0);

        if (!$stock || $live_quantity <= 0) {
            throw new Exception('This product variation has no available stock in the selected warehouse. A Cost Price Adjustment requires existing stock to revalue.');
        }

        $live_avg_price = (float) ($stock->avg_price ?? 0);

        if (round($live_avg_price, 4) !== round((float) $cpa->previous_cost_price, 4)) {
            throw new Exception('The current cost price has changed since this adjustment was created (now ' . $live_avg_price . ', was ' . $cpa->previous_cost_price . '). Please review and re-save the adjustment before approving.');
        }

        $new_cost = (float) $cpa->new_cost_price;

        $batches = ProductVariationBatch::where('business_id', $cpa->business_id)
            ->where('warehouse_id', $cpa->warehouse_id)
            ->where('product_id', $cpa->product_id)
            ->where('product_variation_id', $cpa->product_variation_id)
            ->where('status', Status::ACTIVE)
            ->where('quantity', '>', 0)
            ->lockForUpdate()
            ->get();

        $total_adjustment_amount = 0;

        if ($batches->isNotEmpty()) {
            foreach ($batches as $batch) {
                $batch_qty = (float) $batch->quantity;
                $batch_old_avg = (float) $batch->avg_price;
                $batch_delta = round($batch_qty * ($new_cost - $batch_old_avg), 4);
                $total_adjustment_amount += $batch_delta;

                CostPriceAdjustmentBatch::create([
                    'cost_price_adjustment_batch_id' => generateUuid(),
                    'cost_price_adjustment_id'       => $cpa->cost_price_adjustment_id,
                    'product_variation_batch_id'     => $batch->product_variation_batch_id,
                    'batch_no'                        => $batch->batch_no,
                    'quantity'                         => $batch_qty,
                    'previous_batch_avg_price'         => $batch_old_avg,
                    'new_batch_avg_price'              => $new_cost,
                    'batch_adjustment_amount'          => $batch_delta,
                    'date_created'                      => now(),
                ]);

                $batch->update(['avg_price' => $new_cost]);

                ProductVariationStockTransaction::create([
                    'product_variation_stock_transaction_id' => generateUuid(),
                    'transaction_date'                        => now(),
                    'transaction_type'                         => TransactionType::COST_ADJUSTMENT,
                    'business_id'                              => $cpa->business_id,
                    'product_id'                               => $cpa->product_id,
                    'product_variation_id'                     => $cpa->product_variation_id,
                    'warehouse_id'                              => $cpa->warehouse_id,
                    'conversion_factor'                         => 1,
                    'quantity'                                  => 0,
                    'base_quantity'                             => 0,
                    'unit_price'                                => $new_cost,
                    'total_price'                               => $batch_delta,
                    'quantity_after'                            => $live_quantity,
                    'avg_price_after'                           => $new_cost,
                    'reference_id'                               => $cpa->cost_price_adjustment_id,
                    'reference_type'                             => ReferenceType::COST_PRICE_ADJUSTMENT,
                    'product_variation_batch_id'                 => $batch->product_variation_batch_id,
                    'remarks'                                    => 'Cost Price Adjustment (' . $cpa->reference_no . ') - batch ' . $batch->batch_no,
                    'createdby_id'                               => Auth::id(),
                    'date_created'                               => now(),
                ]);
            }

            $total_adjustment_amount = round($total_adjustment_amount, 4);
            // Every batch now shares $new_cost, so the batch-weighted average
            // trivially equals $new_cost - keep the aggregate stock row's
            // avg_price consistent with it directly rather than re-deriving.
            $stock->update(['avg_price' => $new_cost]);
        } else {
            $total_adjustment_amount = round($live_quantity * ($new_cost - $live_avg_price), 4);

            ProductVariationStockTransaction::create([
                'product_variation_stock_transaction_id' => generateUuid(),
                'transaction_date'                        => now(),
                'transaction_type'                         => TransactionType::COST_ADJUSTMENT,
                'business_id'                              => $cpa->business_id,
                'product_id'                               => $cpa->product_id,
                'product_variation_id'                     => $cpa->product_variation_id,
                'warehouse_id'                              => $cpa->warehouse_id,
                'conversion_factor'                         => 1,
                'quantity'                                  => 0,
                'base_quantity'                             => 0,
                'unit_price'                                => $new_cost,
                'total_price'                               => $total_adjustment_amount,
                'quantity_after'                            => $live_quantity,
                'avg_price_after'                           => $new_cost,
                'reference_id'                               => $cpa->cost_price_adjustment_id,
                'reference_type'                             => ReferenceType::COST_PRICE_ADJUSTMENT,
                'remarks'                                    => 'Cost Price Adjustment - ' . $cpa->reference_no,
                'createdby_id'                               => Auth::id(),
                'date_created'                               => now(),
            ]);

            $stock->update(['avg_price' => $new_cost]);
        }

        $cpa->update([
            'quantity_on_hand'        => $live_quantity,
            'difference_per_unit'     => round($new_cost - $live_avg_price, 4),
            'total_adjustment_amount' => $total_adjustment_amount,
        ]);

        if (abs($total_adjustment_amount) < 0.0001) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $cpa->business_id)->first();

        if (
            !$accounting_setting
            || !$accounting_setting->enable_accounting
            || empty($accounting_setting->default_inventory_account_id)
            || empty($accounting_setting->default_inventory_cost_adjustment_account_id)
        ) {
            throw new Exception('Inventory Account / Inventory Cost Adjustment Account is not configured in Accounting Settings. Please configure it before approving this Cost Price Adjustment.');
        }

        app(AccountingPeriodService::class)->assertPostable($cpa->business_id, now());

        $journal = Journal::where('short', 'ICJ')->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "Inventory Cost Adjustment Voucher" journal category found. Please configure it before approving.');
        }

        $entry_no = generateJVNum($journal->journal_id);

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $cpa->business_id,
            'branch_id'        => $cpa->branch_id,
            'entry_no'         => $entry_no,
            'reference_no'     => $cpa->reference_no,
            'entry_date'       => now(),
            'description'      => 'Auto-generated inventory cost adjustment voucher for approved Cost Price Adjustment ' . $cpa->reference_no,
            'source_type'      => JournalSourceTypes::INVENTORY_COST_ADJUSTMENT,
            'source_id'        => $cpa->cost_price_adjustment_id,
            'status'           => 'posted',
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        $amount = abs($total_adjustment_amount);

        if ($total_adjustment_amount > 0) {
            // Cost increase: Debit Inventory (asset up) / Credit Inventory Cost Adjustment (revaluation gain).
            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_inventory_account_id,
                'debit'                   => $amount,
                'credit'                  => 0,
                'description'             => 'Cost Price Adjustment (Increase) - ' . $cpa->reference_no,
            ]);

            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_inventory_cost_adjustment_account_id,
                'debit'                   => 0,
                'credit'                  => $amount,
                'description'             => 'Cost Price Adjustment (Increase) - ' . $cpa->reference_no,
            ]);
        } else {
            // Cost decrease: Debit Inventory Cost Adjustment (revaluation loss) / Credit Inventory (asset down).
            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_inventory_cost_adjustment_account_id,
                'debit'                   => $amount,
                'credit'                  => 0,
                'description'             => 'Cost Price Adjustment (Decrease) - ' . $cpa->reference_no,
            ]);

            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_inventory_account_id,
                'debit'                   => 0,
                'credit'                  => $amount,
                'description'             => 'Cost Price Adjustment (Decrease) - ' . $cpa->reference_no,
            ]);
        }

        JournalEntryService::assertBalanced($journal_entry->journal_entry_id);
    }

    /**
     * Reverse the Inventory Cost Adjustment Voucher and stock/batch effects
     * created when a Cost Price Adjustment was approved. Idempotent: a no-op
     * if nothing active remains to reverse.
     */
    protected function reversePosting(CostPriceAdjustment $cpa)
    {
        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::INVENTORY_COST_ADJUSTMENT)
            ->where('source_id', $cpa->cost_price_adjustment_id)
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

        $stock_transactions = ProductVariationStockTransaction::where('reference_id', $cpa->cost_price_adjustment_id)
            ->where('reference_type', ReferenceType::COST_PRICE_ADJUSTMENT)
            ->where('is_deleted', 0)
            ->get();

        if ($stock_transactions->isEmpty()) {
            return;
        }

        // ProductVariationStockService::reverseStockTransactions() below only
        // restores ProductVariationStock.avg_price (via recomputeLedger()) -
        // it never touches ProductVariationBatch.avg_price, since nothing
        // else in the codebase needs a batch's avg_price reverted. Restore
        // each affected batch from the value captured at approval time,
        // guarded against a later receipt/adjustment having touched it since.
        $cpa_batches = CostPriceAdjustmentBatch::where('cost_price_adjustment_id', $cpa->cost_price_adjustment_id)->get();

        foreach ($cpa_batches as $cpa_batch) {
            $batch = ProductVariationBatch::lockForUpdate()->find($cpa_batch->product_variation_batch_id);

            if (!$batch) {
                continue;
            }

            if (round((float) $batch->avg_price, 4) !== round((float) $cpa_batch->new_batch_avg_price, 4)) {
                throw new Exception('Cannot cancel this adjustment: batch ' . ($batch->batch_no ?? '-') . ' has since received a new receipt or adjustment. Reverse that first.');
            }

            $batch->update(['avg_price' => $cpa_batch->previous_batch_avg_price]);
        }

        app(ProductVariationStockService::class)->reverseStockTransactions($stock_transactions);
    }
}
