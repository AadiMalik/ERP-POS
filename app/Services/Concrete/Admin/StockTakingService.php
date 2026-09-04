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
use App\Models\StockTaking;
use App\Models\StockTakingDetail;
use App\Models\Warehouse;
use App\Repository\Repository;
use App\Services\Concrete\Admin\ProductVariationStockService;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class StockTakingService
{
    use Auditable;

    protected $model_stock_taking;
    protected $model_stock_taking_details;
    protected $with = [
        'business',
        'branch',
        'warehouse',
        'stockTakingDetails',
        'stockTakingDetails.product',
        'stockTakingDetails.productVariation',
        'stockTakingDetails.unit',
    ];

    public function __construct()
    {
        $this->model_stock_taking = new Repository(new StockTaking());
        $this->model_stock_taking_details = new Repository(new StockTakingDetail());
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
            $wh[] = ['stock_taking_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['stock_taking_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::INVENTORYMANAGER,
            RoleNames::BRANCHADMIN,
            RoleNames::POSMANAGER,
        ];

        $datatable = $this->model_stock_taking->getModel()::with($this->with)
            ->withCount('stockTakingDetails as total_products')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('stock_taking_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('stock_taking_date', function ($item) {
                return !empty($item->stock_taking_date)
                    ? localDate($item->stock_taking_date)
                    : 'N/A';
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
            ->addColumn('total_difference_value', function ($item) {
                return currency($item->total_difference_value ?? 0);
            })
            ->addColumn('status', function ($item) {

                $statuses = [
                    Status::PENDING   => ucfirst(Status::PENDING),
                    Status::APPROVED  => ucfirst(Status::APPROVED),
                    Status::CANCELLED => ucfirst(Status::CANCELLED),
                ];

                $html = "<select class='form-select form-select-sm change-status'
                data-id='{$item->stock_taking_id}'>";

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
                        href='" . route('stock-taking.edit', $item->stock_taking_id) . "'
                        id='editStockTaking'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='Only pending stock takings can be edited'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                $printButton = "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('stock-taking.print', $item->stock_taking_id) . "' title='Print'>
                    <i class='fa fa-print'></i>
                    </a>";

                $deleteButton = $item->status !== Status::CANCELLED
                    ? "<a class='btn btn-icon btn-outline-danger'
                    id='deleteStockTaking'
                    data-id='{$item->stock_taking_id}'>
                    <i class='fa fa-trash'></i>
                    </a>"
                    : '';

                return $editButton . $printButton . $deleteButton;
            })
            ->rawColumns(['stock_taking_date', 'business', 'branch', 'warehouse', 'total_products', 'total_difference_value', 'status', 'action'])
            ->make(true);
    }

    /**
     * Current stock for every product/variation in a warehouse, used to seed
     * a new Stock Taking's rows with system quantity + current avg cost.
     */
    public function getSystemStock($warehouse_id)
    {
        $stocks = app(ProductVariationStockService::class)->getByWarehouse($warehouse_id);

        return $stocks->map(function ($stock) {
            return [
                'product_id'           => $stock->product_id,
                'product_name'         => $stock->product->name ?? '',
                'product_variation_id' => $stock->product_variation_id,
                'variation_name'       => $stock->productVariation->name ?? '',
                'unit_id'              => $stock->productVariation->base_unit_id ?? null,
                'unit_name'            => $stock->productVariation->unit->name ?? 'N/A',
                'system_quantity'      => $stock->quantity,
                'unit_cost'            => $stock->avg_price,
                'track_serial_number'  => (bool) ($stock->productVariation->track_serial_number ?? false),
            ];
        })->values();
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

            //====================================
            // Update
            //====================================

            $is_update = !empty($obj['stock_taking_id']);
            $old_snapshot = null;

            if ($is_update) {

                $stock_taking = $this->model_stock_taking->getModel()::findOrFail($obj['stock_taking_id']);

                if ($stock_taking->status !== Status::PENDING) {
                    throw new Exception('Only pending stock takings can be updated.');
                }

                $old_snapshot = [
                    'warehouse_id'               => $stock_taking->warehouse_id,
                    'stock_taking_date'          => $stock_taking->stock_taking_date,
                    'total_difference_quantity'  => $stock_taking->total_difference_quantity,
                    'total_difference_value'     => $stock_taking->total_difference_value,
                ];

                $stock_taking->update([
                    'business_id'         => $business_id,
                    'branch_id'           => $branch_id,
                    'warehouse_id'        => $warehouse->warehouse_id,
                    'stock_taking_date'   => $obj['stock_taking_date'],
                    'reference'           => $obj['reference'] ?? null,
                    'description'         => $obj['description'] ?? null,
                    'updatedby_id'        => Auth::id(),
                    'date_updated'        => now(),
                ]);

                $this->model_stock_taking_details->getModel()::where('stock_taking_id', $stock_taking->stock_taking_id)
                    ->delete();
            }

            //====================================
            // Create
            //====================================

            else {

                $stock_taking = $this->model_stock_taking->create([
                    'stock_taking_id'    => generateUuid(),
                    'business_id'        => $business_id,
                    'branch_id'          => $branch_id,
                    'warehouse_id'       => $warehouse->warehouse_id,
                    'stock_taking_no'    => $obj['stock_taking_no'],
                    'stock_taking_date'  => $obj['stock_taking_date'],
                    'reference'          => $obj['reference'] ?? null,
                    'description'        => $obj['description'] ?? null,
                    'status'             => Status::PENDING,
                    'createdby_id'       => Auth::id(),
                    'date_created'       => now(),
                ]);
            }

            //====================================
            // Save Items
            //====================================

            $total_difference_quantity = 0;
            $total_difference_value = 0;
            $has_line = false;

            foreach ($obj['products'] as $product) {

                if (empty($product['product_id']) || empty($product['product_variation_id'])) {
                    continue;
                }

                $has_line = true;

                $stock = ProductVariationStock::where('business_id', $business_id)
                    ->where('warehouse_id', $warehouse->warehouse_id)
                    ->where('product_id', $product['product_id'])
                    ->where('product_variation_id', $product['product_variation_id'])
                    ->first();

                $system_quantity = (float) ($stock->quantity ?? 0);
                $unit_cost = (float) ($stock->avg_price ?? 0);
                $physical_quantity = (float) ($product['physical_quantity'] ?? 0);

                // Serial-tracked variations are reconciled unit-by-unit via
                // the Serial Number screens (mark lost/damaged, add a found
                // unit), not by a blind quantity override here - force the
                // physical count to match system stock so this line can
                // never silently drift out of sync with the serial ledger,
                // even if a tampered request submits a different value.
                $variation = \App\Models\ProductVariation::find($product['product_variation_id']);
                if ($variation && $variation->track_serial_number) {
                    $physical_quantity = $system_quantity;
                }

                $difference_quantity = $physical_quantity - $system_quantity;
                $difference_value = $difference_quantity * $unit_cost;

                $total_difference_quantity += $difference_quantity;
                $total_difference_value += $difference_value;

                $this->model_stock_taking_details->create([
                    'stock_taking_detail_id' => generateUuid(),
                    'stock_taking_id'        => $stock_taking->stock_taking_id,
                    'product_id'             => $product['product_id'],
                    'product_variation_id'   => $product['product_variation_id'],
                    'unit_id'                => $product['unit_id'] ?? null,
                    'system_quantity'        => $system_quantity,
                    'physical_quantity'      => $physical_quantity,
                    'difference_quantity'    => $difference_quantity,
                    'unit_cost'              => $unit_cost,
                    'difference_value'       => $difference_value,
                    'reason'                 => $product['reason'] ?? null,
                    'description'            => $product['description'] ?? null,
                    'createdby_id'           => Auth::id(),
                    'date_created'           => now(),
                ]);
            }

            if (!$has_line) {
                throw new Exception('Please add at least one product to count.');
            }

            $stock_taking->update([
                'total_difference_quantity' => $total_difference_quantity,
                'total_difference_value'    => $total_difference_value,
            ]);

            DB::commit();

            $this->logActivity(
                'stock-taking',
                $stock_taking->stock_taking_id,
                $is_update ? 'updated' : 'created',
                $old_snapshot,
                [
                    'warehouse_id'              => $stock_taking->warehouse_id,
                    'stock_taking_date'         => $stock_taking->stock_taking_date,
                    'total_difference_quantity' => $total_difference_quantity,
                    'total_difference_value'    => $total_difference_value,
                ],
                null,
                $business_id,
                $branch_id
            );

            return $stock_taking;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($stock_taking_id)
    {
        return $this->model_stock_taking->with($this->with)->find($stock_taking_id);
    }

    public function getDetails($stock_taking_id)
    {
        try {
            $stock_taking = $this->model_stock_taking->getModel()::with($this->with)->findOrFail($stock_taking_id);

            $data = [
                'header' => [
                    'stock_taking_id'    => $stock_taking->stock_taking_id,
                    'warehouse_id'       => $stock_taking->warehouse_id,
                    'branch_id'          => $stock_taking->branch_id,
                    'stock_taking_no'    => $stock_taking->stock_taking_no,
                    'stock_taking_date'  => $stock_taking->stock_taking_date,
                    'reference'          => $stock_taking->reference,
                    'description'        => $stock_taking->description,
                    'total_difference_quantity' => $stock_taking->total_difference_quantity,
                    'total_difference_value'    => $stock_taking->total_difference_value,
                    'status'             => $stock_taking->status,
                ],
                'details' => []
            ];

            foreach ($stock_taking->stockTakingDetails as $detail) {
                $data['details'][] = [
                    'stock_taking_detail_id' => $detail->stock_taking_detail_id,
                    'product_id'             => $detail->product_id,
                    'product_name'           => $detail->product->name ?? '',
                    'product_variation_id'   => $detail->product_variation_id,
                    'variation_name'         => $detail->productVariation->name ?? '',
                    'unit_id'                => $detail->unit_id,
                    'unit_name'              => $detail->unit->name ?? 'N/A',
                    'system_quantity'        => $detail->system_quantity,
                    'physical_quantity'      => $detail->physical_quantity,
                    'difference_quantity'    => $detail->difference_quantity,
                    'unit_cost'              => $detail->unit_cost,
                    'difference_value'       => $detail->difference_value,
                    'reason'                 => $detail->reason,
                    'track_serial_number'    => (bool) ($detail->productVariation->track_serial_number ?? false),
                ];
            }

            return $data;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Compares each line's live stock quantity right now against the
     * system_quantity snapshot captured when the count was originally saved.
     * applyStockTakingPosting() already always recomputes/posts against the
     * LIVE quantity (never the stale snapshot) so the posted numbers are
     * never wrong - this is purely so the approver can be warned that stock
     * moved since the count was taken, before they confirm.
     */
    public function checkDrift($stock_taking_id): array
    {
        $stock_taking = $this->model_stock_taking->getModel()::with('stockTakingDetails.product')->findOrFail($stock_taking_id);

        $drifted = [];

        foreach ($stock_taking->stockTakingDetails as $detail) {
            $live_quantity = (float) (ProductVariationStock::where('business_id', $stock_taking->business_id)
                ->where('warehouse_id', $stock_taking->warehouse_id)
                ->where('product_id', $detail->product_id)
                ->where('product_variation_id', $detail->product_variation_id)
                ->value('quantity') ?? 0);

            if (abs($live_quantity - (float) $detail->system_quantity) > 0.0009) {
                $drifted[] = [
                    'product_name'            => $detail->product->name ?? '',
                    'counted_system_quantity' => (float) $detail->system_quantity,
                    'current_system_quantity' => $live_quantity,
                    'physical_quantity'       => (float) $detail->physical_quantity,
                ];
            }
        }

        return $drifted;
    }

    public function status($obj)
    {
        DB::beginTransaction();

        try {
            $stock_taking = $this->model_stock_taking->getModel()::with($this->with)->findOrFail($obj['stock_taking_id']);
            $old_status = $stock_taking->status;
            $new_status = $obj['status'];

            $stock_taking->update([
                'status'       => $new_status,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            if ($new_status === Status::APPROVED && $old_status !== Status::APPROVED) {
                $this->applyStockTakingPosting($stock_taking);
            } elseif ($old_status === Status::APPROVED && $new_status !== Status::APPROVED) {
                $this->reverseStockTakingPosting($stock_taking);
            }

            DB::commit();

            $this->logActivity(
                'stock-taking',
                $stock_taking->stock_taking_id,
                $new_status === Status::APPROVED ? 'approved' : 'status_changed',
                ['status' => $old_status],
                ['status' => $new_status]
            );
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $stock_taking;
    }

    public function delete($stock_taking_id)
    {
        DB::beginTransaction();

        try {
            $stock_taking = $this->model_stock_taking->getModel()::with($this->with)->findOrFail($stock_taking_id);
            $old_status = $stock_taking->status;

            if ($stock_taking->status === Status::APPROVED) {
                $this->reverseStockTakingPosting($stock_taking);
            }

            $stock_taking->update([
                'is_deleted'   => 1,
                'status'       => Status::CANCELLED,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            DB::commit();

            $this->logActivity(
                'stock-taking',
                $stock_taking->stock_taking_id,
                'deleted',
                [
                    'status'                     => $old_status,
                    'total_difference_quantity'  => $stock_taking->total_difference_quantity,
                    'total_difference_value'     => $stock_taking->total_difference_value,
                ],
                null,
                null,
                $stock_taking->business_id,
                $stock_taking->branch_id
            );

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Auto-post an Inventory Count Voucher and adjustment transactions when a
     * Stock Taking is approved. The difference applied is recomputed against
     * the LIVE stock quantity at posting time (not the snapshot taken when
     * the draft was saved), so a stale draft can never mis-adjust stock.
     * Idempotent: a no-op if an active voucher already exists.
     */
    protected function applyStockTakingPosting(StockTaking $stock_taking)
    {
        $existing = JournalEntry::where('source_type', JournalSourceTypes::INVENTORY_COUNT)
            ->where('source_id', $stock_taking->stock_taking_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $stock_taking->business_id)->first();

        if (
            !$accounting_setting
            || !$accounting_setting->enable_accounting
            || empty($accounting_setting->default_inventory_account_id)
            || empty($accounting_setting->default_stock_adjustment_account_id)
        ) {
            throw new Exception('Inventory Account / Stock Adjustment Account is not configured in Accounting Settings. Please configure it before approving stock taking.');
        }

        app(\App\Services\Concrete\Admin\AccountingPeriodService::class)->assertPostable($stock_taking->business_id, now());

        $total_increase_value = 0;
        $total_decrease_value = 0;

        foreach ($stock_taking->stockTakingDetails as $detail) {

            $stock = ProductVariationStock::where('business_id', $stock_taking->business_id)
                ->where('warehouse_id', $stock_taking->warehouse_id)
                ->where('product_id', $detail->product_id)
                ->where('product_variation_id', $detail->product_variation_id)
                ->lockForUpdate()
                ->first();

            $live_quantity = (float) ($stock->quantity ?? 0);
            $live_avg_price = (float) ($stock->avg_price ?? 0);
            $difference_quantity = (float) $detail->physical_quantity - $live_quantity;

            // Persist the value actually applied (against live stock) for audit/reversal.
            $detail->update([
                'system_quantity'     => $live_quantity,
                'difference_quantity' => $difference_quantity,
                'unit_cost'           => $live_avg_price,
                'difference_value'    => $difference_quantity * $live_avg_price,
            ]);

            if ($difference_quantity == 0) {
                continue;
            }

            $base_quantity = abs($difference_quantity);
            $line_value = $base_quantity * $live_avg_price;

            if ($difference_quantity > 0) {
                $new_qty = $live_quantity + $base_quantity;
                $transaction_type = TransactionType::STOCK_TAKE_INCREASE;
                $total_increase_value += $line_value;
            } else {
                $new_qty = $live_quantity - $base_quantity;
                $transaction_type = TransactionType::STOCK_TAKE_DECREASE;
                $total_decrease_value += $line_value;
            }

            // Avg cost is unaffected by a count adjustment - the adjustment is
            // valued at the current avg cost, it doesn't establish a new one.
            if ($stock) {
                $stock->update(['quantity' => $new_qty]);
            } else {
                $stock = ProductVariationStock::create([
                    'product_variation_stock_id' => generateUuid(),
                    'business_id'                => $stock_taking->business_id,
                    'warehouse_id'               => $stock_taking->warehouse_id,
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
                'transaction_type'                        => $transaction_type,
                'business_id'                             => $stock_taking->business_id,
                'product_id'                              => $detail->product_id,
                'product_variation_id'                    => $detail->product_variation_id,
                'warehouse_id'                             => $stock_taking->warehouse_id,
                'unit_id'                                  => $detail->unit_id,
                'conversion_factor'                        => 1,
                'quantity'                                 => $base_quantity,
                'base_quantity'                            => $base_quantity,
                'unit_price'                               => $live_avg_price,
                'total_price'                              => $line_value,
                'quantity_after'                           => $new_qty,
                'avg_price_after'                          => $live_avg_price,
                'reference_id'                              => $stock_taking->stock_taking_id,
                'reference_type'                            => ReferenceType::STOCK_TAKING,
                'remarks'                                   => 'Auto-created on approval of stock taking ' . $stock_taking->stock_taking_no,
                'createdby_id'                              => Auth::id(),
                'date_created'                              => now(),
            ]);
        }

        if ($total_increase_value <= 0 && $total_decrease_value <= 0) {
            return;
        }

        $journal = Journal::where('short', 'ICV')->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "Inventory Count Voucher" journal category found. Please configure it before approving stock taking.');
        }

        $entry_no = generateJVNum($journal->journal_id);

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $stock_taking->business_id,
            'branch_id'        => $stock_taking->branch_id,
            'entry_no'         => $entry_no,
            'reference_no'     => $stock_taking->stock_taking_no,
            'entry_date'       => now(),
            'description'      => 'Auto-generated inventory count voucher for approved stock taking ' . $stock_taking->stock_taking_no,
            'source_type'      => JournalSourceTypes::INVENTORY_COUNT,
            'source_id'        => $stock_taking->stock_taking_id,
            'status'           => 'posted',
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        if ($total_increase_value > 0) {
            // Extra stock found: Debit Inventory (asset up) / Credit Stock Adjustment (gain).
            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_inventory_account_id,
                'debit'                   => $total_increase_value,
                'credit'                  => 0,
                'description'             => 'Stock Taking Gain - ' . $stock_taking->stock_taking_no,
            ]);

            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_stock_adjustment_account_id,
                'debit'                   => 0,
                'credit'                  => $total_increase_value,
                'description'             => 'Stock Taking Gain - ' . $stock_taking->stock_taking_no,
            ]);
        }

        if ($total_decrease_value > 0) {
            // Stock shortage: Debit Stock Adjustment (loss) / Credit Inventory (asset down).
            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_stock_adjustment_account_id,
                'debit'                   => $total_decrease_value,
                'credit'                  => 0,
                'description'             => 'Stock Taking Loss - ' . $stock_taking->stock_taking_no,
            ]);

            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $accounting_setting->default_inventory_account_id,
                'debit'                   => 0,
                'credit'                  => $total_decrease_value,
                'description'             => 'Stock Taking Loss - ' . $stock_taking->stock_taking_no,
            ]);
        }

        \App\Services\Concrete\Admin\JournalEntryService::assertBalanced($journal_entry->journal_entry_id);
    }

    /**
     * Reverse the Inventory Count Voucher and stock effects created when a
     * Stock Taking was approved. Idempotent: a no-op if nothing active
     * remains to reverse.
     */
    protected function reverseStockTakingPosting(StockTaking $stock_taking)
    {
        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::INVENTORY_COUNT)
            ->where('source_id', $stock_taking->stock_taking_id)
            ->where('is_deleted', 0)
            ->first();

        if ($journal_entry) {
            app(\App\Services\Concrete\Admin\AccountingPeriodService::class)->assertPostable($journal_entry->business_id, $journal_entry->entry_date);

            $journal_entry->update([
                'is_deleted'   => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);
        }

        $stock_transactions = ProductVariationStockTransaction::where('reference_type', ReferenceType::STOCK_TAKING)
            ->where('reference_id', $stock_taking->stock_taking_id)
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
        return $this->model_stock_taking->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
