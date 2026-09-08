<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\ReferenceType;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Models\Branch;
use App\Models\GoodReceiptNote;
use App\Models\InventorySetting;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
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
use Yajra\DataTables\DataTables;

class ProductVariationStockService
{
    use Auditable;

    protected $model_product_variation_stock;
    protected $with = ['business', 'product', 'productVariation', 'warehouse', 'createdBy', 'updatedBy', 'deletedBy'];

    public function __construct()
    {
        $this->model_product_variation_stock = new Repository(new ProductVariationStock());
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
            RoleNames::BUSINESSADMIN,
            RoleNames::INVENTORYMANAGER,
            RoleNames::BRANCHADMIN,
            RoleNames::POSMANAGER,
        ];
        $datatable = $this->model_product_variation_stock->getModel()::where($wh)
            ->with($this->with)
            ->where('is_deleted', 0)
            ->orderBy('date_created', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('product', function ($item) {

                return $item->product?->name ?? '-';
            })
            ->addColumn('unit', function ($item) {

                return $item->productVariation?->unit->name ?? '-';
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
            ->addColumn('avg_price', function ($item) {

                return decimal($item->avg_price ?? 0);
            })
            ->addColumn('quantity', function ($item) {

                return decimal($item->quantity ?? 0);
            })
            ->addColumn('reserved_quantity', function ($item) {

                return decimal($item->reserved_quantity ?? 0);
            })
            ->addColumn('available_quantity', function ($item) {

                return decimal($item->available_quantity);
            })
            ->addColumn('status', function ($item) {

                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusProductVariationStock"
                        type="checkbox"
                        data-id="' . $item->product_variation_stock_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-info mr-2'
                    id='viewStockHistory'
                    title='Stock History'
                    data-id='{$item->product_variation_stock_id}'>

                    <i class='fa fa-history'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteProductVariationStock'
                    data-id='{$item->product_variation_stock_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['business', 'product','unit','avg_price','quantity', 'productVariation', 'warehouse', 'status', 'action'])
            ->make(true);
    }
    public function status($product_variation_stock_id)
    {
        return $this->model_product_variation_stock->update([
            'status' => ($this->model_product_variation_stock->find($product_variation_stock_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $product_variation_stock_id);
    }

    /**
     * Only allowed when the stock row's quantity is already zero - there is
     * no valid "reversal" for deleting a row that still carries an on-hand
     * balance (use a stock transaction reversal instead, which keeps the
     * ledger consistent). See Phase 1 plan's "Stock Movement Deletion/
     * Reversal fix".
     */
    public function delete($product_variation_stock_id)
    {
        $stock = $this->model_product_variation_stock->getModel()::where('is_deleted', 0)->findOrFail($product_variation_stock_id);

        if (abs((float) $stock->quantity) > 0.0009) {
            throw new Exception('This stock record still has an on-hand quantity of ' . $stock->quantity . ' and cannot be deleted. Reverse its stock transactions instead.');
        }

        $result = $this->model_product_variation_stock->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $product_variation_stock_id);

        $this->logActivity('stock', $product_variation_stock_id, 'deleted', $stock->only(['quantity', 'avg_price']), null, null, $stock->business_id);

        return $result;
    }

    public function getAll()
    {
        return $this->model_product_variation_stock->getModel()::with($this->with)
            ->where('business_id', Auth::user()->business_id)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getAllActive()
    {
        return $this->model_product_variation_stock->getModel()::with($this->with)
            ->where('business_id', Auth::user()->business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getByProduct($product_id)
    {
        return $this->model_product_variation_stock->getModel()::with($this->with)
            ->where('product_id', $product_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getByProductVariation($product_variation_id)
    {
        return $this->model_product_variation_stock->getModel()::with($this->with)
            ->where('product_variation_id', $product_variation_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getByWarehouse($warehouse_id)
    {
        return $this->model_product_variation_stock->getModel()::with($this->with)
            ->where('warehouse_id', $warehouse_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getByBusiness($business_id)
    {
        return $this->model_product_variation_stock->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }

    /**
     * Replay every active stock transaction for one product+variation+warehouse
     * in chronological order, rewriting each transaction's quantity_after /
     * avg_price_after snapshot and the aggregate stock row's quantity /
     * avg_price to match. Called after any transaction is reversed/deleted so
     * later transactions' stored running balances never go stale, and the
     * running balance shown in the ledger always matches the Stock table.
     */
    public function recomputeLedger($business_id, $warehouse_id, $product_id, $product_variation_id)
    {
        $transactions = ProductVariationStockTransaction::where('business_id', $business_id)
            ->where('warehouse_id', $warehouse_id)
            ->where('product_id', $product_id)
            ->where('product_variation_id', $product_variation_id)
            ->where('is_deleted', 0)
            ->orderBy('transaction_date')
            ->orderBy('date_created')
            ->orderBy('product_variation_stock_transaction_id')
            ->get();

        $quantity = 0;
        $avg_price = 0;

        foreach ($transactions as $transaction) {
            if (TransactionType::isInbound($transaction->transaction_type)) {
                $new_quantity = $quantity + $transaction->base_quantity;
                $avg_price = $new_quantity > 0
                    ? (($quantity * $avg_price) + $transaction->total_price) / $new_quantity
                    : 0;
                $quantity = $new_quantity;
            } else {
                $quantity -= $transaction->base_quantity;
            }

            $transaction->update([
                'quantity_after'   => $quantity,
                'avg_price_after'  => $avg_price,
            ]);
        }

        $stock = ProductVariationStock::where('business_id', $business_id)
            ->where('warehouse_id', $warehouse_id)
            ->where('product_id', $product_id)
            ->where('product_variation_id', $product_variation_id)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            $stock->update([
                'quantity'  => $quantity,
                'avg_price' => $avg_price,
            ]);
        }

        return ['quantity' => $quantity, 'avg_price' => $avg_price];
    }

    /**
     * Full stock ledger for a single Stock row: every active movement in
     * chronological order plus the current available balance, so the UI can
     * render a Stock History view that always matches the Stock table.
     */
    public function getLedger($product_variation_stock_id)
    {
        $stock = $this->model_product_variation_stock->getModel()::with($this->with)
            ->findOrFail($product_variation_stock_id);

        $transactions = ProductVariationStockTransaction::with(['unit'])
            ->where('business_id', $stock->business_id)
            ->where('warehouse_id', $stock->warehouse_id)
            ->where('product_id', $stock->product_id)
            ->where('product_variation_id', $stock->product_variation_id)
            ->where('is_deleted', 0)
            ->orderBy('transaction_date')
            ->orderBy('date_created')
            ->orderBy('product_variation_stock_transaction_id')
            ->get();

        $transaction_types = TransactionType::getOptions();
        $source_modules = ReferenceType::getOptions();

        $ledger = $transactions->map(function ($transaction) use ($transaction_types, $source_modules) {
            $is_inbound = TransactionType::isInbound($transaction->transaction_type);

            return [
                'transaction_date'   => $transaction->transaction_date,
                'direction'          => $is_inbound ? 'in' : 'out',
                'transaction_type'   => $transaction_types[$transaction->transaction_type] ?? ucfirst($transaction->transaction_type),
                'source_module'      => $source_modules[$transaction->reference_type] ?? ucfirst($transaction->reference_type ?? '-'),
                'reference_no'       => $this->resolveReferenceNo($transaction->reference_type, $transaction->reference_id),
                'unit'               => $transaction->unit?->name ?? '-',
                'quantity'           => $transaction->base_quantity,
                'running_balance'    => $transaction->quantity_after,
                'remarks'            => $transaction->remarks,
            ];
        });

        return [
            'product' => $stock->product?->name ?? '-',
            'product_variation' => $stock->productVariation?->name ?? '-',
            'warehouse' => $stock->warehouse?->name ?? '-',
            'current_balance' => $stock->quantity,
            'current_avg_price' => $stock->avg_price,
            'ledger' => $ledger,
        ];
    }

    /**
     * Best-effort resolution of a human-readable document number for a stock
     * transaction's source. Delegates to the shared ReferenceResolverService
     * so this and Reports\StockLedgerReportService resolve reference types
     * identically.
     */
    protected function resolveReferenceNo($reference_type, $reference_id)
    {
        return app(ReferenceResolverService::class)->resolveDocNo($reference_type, $reference_id);
    }

    /**
     * Find-or-create the ProductVariationBatch a receiving line (direct
     * Purchase, GRN, Opening Stock) belongs to, rolling its quantity/avg_price
     * forward the same way the aggregate ProductVariationStock row is. No-op
     * (returns null) for a variation that doesn't opt into batch/expiry
     * tracking, or a line that didn't supply a batch_no - batch tracking
     * stays fully optional per product.
     */
    public function upsertReceiptBatch($business_id, $warehouse_id, $product_id, $product_variation_id, $batch_no, $manufacturing_date, $expiry_date, $base_quantity, $line_cost)
    {
        if (empty($batch_no)) {
            return null;
        }

        $variation = ProductVariation::find($product_variation_id);

        if (!$variation || (!$variation->track_batch && !$variation->track_expiry)) {
            return null;
        }

        $batch = ProductVariationBatch::where('business_id', $business_id)
            ->where('warehouse_id', $warehouse_id)
            ->where('product_id', $product_id)
            ->where('product_variation_id', $product_variation_id)
            ->where('batch_no', $batch_no)
            ->lockForUpdate()
            ->first();

        $existing_qty = $batch->quantity ?? 0;
        $existing_avg = $batch->avg_price ?? 0;
        $new_qty = $existing_qty + $base_quantity;
        $new_avg = $new_qty > 0 ? ((($existing_qty * $existing_avg) + $line_cost) / $new_qty) : 0;

        if ($batch) {
            $batch->update([
                'quantity'  => $new_qty,
                'avg_price' => $new_avg,
            ]);
        } else {
            $batch = ProductVariationBatch::create([
                'product_variation_batch_id' => generateUuid(),
                'batch_no'                   => $batch_no,
                'business_id'                => $business_id,
                'product_id'                 => $product_id,
                'product_variation_id'       => $product_variation_id,
                'warehouse_id'               => $warehouse_id,
                'avg_price'                  => $new_avg,
                'quantity'                   => $base_quantity,
                'manufacturing_date'         => $manufacturing_date,
                'expiry_date'                => $expiry_date,
                'status'                     => 'active',
                'createdby_id'               => Auth::id(),
                'date_created'               => now(),
            ]);
        }

        return $batch->product_variation_batch_id;
    }

    /**
     * Soft-delete a set of active stock transactions (reversing a purchase,
     * GRN, purchase return, sale, sale return, or void), reverse each
     * transaction's batch delta when it carries a product_variation_batch_id,
     * and recompute the aggregate ProductVariationStock + ledger running
     * balances for every product/variation/warehouse touched. Every apply-
     * and-reverse pair in Purchase/Grn/PurchaseReturn/Order/OrderReturn
     * services funnels its reversal through here so the aggregate table, the
     * ledger, and per-batch quantities never drift out of sync.
     */
    public function reverseStockTransactions($transactions)
    {
        $transactions = $transactions instanceof \Illuminate\Support\Collection ? $transactions : collect($transactions);

        if ($transactions->isEmpty()) {
            return;
        }

        $transactions->each(function ($transaction) {
            $transaction->update([
                'is_deleted'   => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            if ($transaction->product_variation_batch_id) {
                // An inbound transaction (purchase/GRN/sale return) had added
                // stock to its batch, so reversing it subtracts; an outbound
                // transaction (sale, purchase return) had drawn the batch
                // down, so reversing it adds back.
                $sign = TransactionType::isInbound($transaction->transaction_type) ? -1 : 1;
                $this->adjustBatchQuantity($transaction->product_variation_batch_id, $sign * (float) $transaction->base_quantity);
            }
        });

        $affected = $transactions->unique(function ($transaction) {
            return $transaction->business_id . '|' . $transaction->warehouse_id . '|' .
                $transaction->product_id . '|' . $transaction->product_variation_id;
        });

        foreach ($affected as $transaction) {
            $this->recomputeLedger(
                $transaction->business_id,
                $transaction->warehouse_id,
                $transaction->product_id,
                $transaction->product_variation_id
            );
        }
    }

    /**
     * Increment/decrement one batch's on-hand quantity by $delta (negative to
     * decrement), floored at zero as a guard. Used to reverse a receipt, post
     * a purchase return, or restore stock from a sale return/void - every
     * caller that isn't the initial receipt or the FEFO sale draw-down.
     */
    public function adjustBatchQuantity($product_variation_batch_id, $delta)
    {
        if (empty($product_variation_batch_id) || abs((float) $delta) < 0.0001) {
            return;
        }

        $batch = ProductVariationBatch::lockForUpdate()->find($product_variation_batch_id);

        if (!$batch) {
            return;
        }

        $batch->update([
            'quantity' => max(0, (float) $batch->quantity + (float) $delta),
        ]);
    }

    /**
     * FEFO/FIFO draw-down: lock and return the ordered list of batches (each
     * paired with the quantity to consume from it) needed to cover
     * $base_quantity for a batch/expiry-tracked variation, honoring the
     * business's batch_selection_strategy and block_expired_sale settings.
     * $warehouse_ids is the branch's linked warehouses in priority order
     * (see getLinkedWarehouseIds()) - FEFO/FIFO is applied across ALL of
     * them together (earliest expiry wins regardless of which warehouse it's
     * in), with warehouse priority only breaking a tie where expiry AND
     * received-date are identical. Returns null (never a partial list) when
     * the tracked batches on hand can't cover the full quantity, so the
     * caller's existing "insufficient stock" handling applies the same
     * whether or not batches are involved.
     */
    public function pickBatchesForSale($business_id, array $warehouse_ids, $product_id, $product_variation_id, $base_quantity, ?InventorySetting $setting = null)
    {
        if (empty($warehouse_ids)) {
            return null;
        }

        $query = ProductVariationBatch::where('business_id', $business_id)
            ->whereIn('warehouse_id', $warehouse_ids)
            ->where('product_id', $product_id)
            ->where('product_variation_id', $product_variation_id)
            ->where('status', Status::ACTIVE)
            ->where('quantity', '>', 0)
            // Stable lock-acquisition order (independent of FEFO/priority
            // ordering below) so two concurrent multi-warehouse sales that
            // both touch these warehouses always take row locks in the same
            // sequence - prevents a lock-order deadlock between them.
            ->orderBy('warehouse_id')
            ->orderBy('product_variation_batch_id')
            ->lockForUpdate();

        if ($setting && $setting->enable_expiry_date && $setting->block_expired_sale) {
            $query->where(function ($q) {
                $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now()->toDateString());
            });
        }

        $batches = $query->get();

        $warehouse_priority = array_flip(array_values($warehouse_ids));

        $sort_key = function ($batch) use ($setting, $warehouse_priority) {
            $date_key = $batch->date_created ? strtotime($batch->date_created) : 0;

            if (($setting?->batch_selection_strategy ?? 'fefo') === 'fifo') {
                return [$date_key, $warehouse_priority[$batch->warehouse_id] ?? PHP_INT_MAX];
            }

            // FEFO: null expiry sorts last, earliest expiry first, then
            // earliest received, then the branch's warehouse priority.
            $expiry_key = $batch->expiry_date ? strtotime($batch->expiry_date) : PHP_INT_MAX;

            return [$expiry_key, $date_key, $warehouse_priority[$batch->warehouse_id] ?? PHP_INT_MAX];
        };

        $batches = $batches->sort(function ($a, $b) use ($sort_key) {
            return $sort_key($a) <=> $sort_key($b);
        })->values();

        $remaining = (float) $base_quantity;
        $picks = [];

        foreach ($batches as $batch) {
            if ($remaining <= 0.0001) {
                break;
            }

            $take = min((float) $batch->quantity, $remaining);
            $picks[] = ['batch' => $batch, 'warehouse_id' => $batch->warehouse_id, 'base_quantity' => $take];
            $remaining -= $take;
        }

        if ($remaining > 0.0001) {
            return null;
        }

        return $picks;
    }

    /**
     * Non-batch-tracked counterpart to pickBatchesForSale(): greedily draws
     * $base_quantity from the branch's linked warehouses in priority order
     * (no expiry to sort by, so priority is the only signal). Returns null
     * when the combined available stock can't cover the full quantity.
     */
    public function pickWarehousesForSale($business_id, array $warehouse_ids, $product_id, $product_variation_id, $base_quantity)
    {
        if (empty($warehouse_ids)) {
            return null;
        }

        $stocks = ProductVariationStock::where('business_id', $business_id)
            ->whereIn('warehouse_id', $warehouse_ids)
            ->where('product_id', $product_id)
            ->where('product_variation_id', $product_variation_id)
            // Stable lock-acquisition order, same reasoning as pickBatchesForSale().
            ->orderBy('warehouse_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('warehouse_id');

        $remaining = (float) $base_quantity;
        $picks = [];

        foreach ($warehouse_ids as $warehouse_id) {
            if ($remaining <= 0.0001) {
                break;
            }

            $stock = $stocks->get($warehouse_id);
            $available = $stock ? ((float) $stock->quantity - (float) $stock->reserved_quantity) : 0.0;

            if ($available <= 0.0001) {
                continue;
            }

            $take = min($available, $remaining);
            $picks[] = ['warehouse_id' => $warehouse_id, 'stock' => $stock, 'base_quantity' => $take];
            $remaining -= $take;
        }

        if ($remaining > 0.0001) {
            return null;
        }

        return $picks;
    }

    /**
     * Warehouse ids linked to a branch (branch_warehouses pivot), active and
     * non-deleted only, in priority order. The single source of truth for
     * "which warehouses feed this branch's stock" - every surface (POS,
     * website/mobile API, desktop POS sync) must resolve warehouses through
     * this instead of a warehouse's own (legacy, display-only) branch_id.
     */
    public function getLinkedWarehouseIds($business_id, $branch_id): array
    {
        if (empty($business_id) || empty($branch_id)) {
            return [];
        }

        $branch = Branch::where('branch_id', $branch_id)
            ->where('business_id', $business_id)
            ->first();

        if (!$branch) {
            return [];
        }

        return $branch->warehouses()
            ->where('warehouses.status', Status::ACTIVE)
            ->where('warehouses.is_deleted', 0)
            ->pluck('warehouses.warehouse_id')
            ->all();
    }

    /**
     * Combined available (non-expired, unreserved) stock across every
     * warehouse linked to a branch, for one product variation - THE figure
     * every surface must show/validate against instead of a single
     * warehouse's quantity. See getAvailableStockForBranchBulk() for the
     * many-variations-at-once form listing pages need to avoid N+1 queries.
     */
    public function getAvailableStockForBranch($business_id, $branch_id, $product_id, $product_variation_id): float
    {
        $sums = $this->getAvailableStockForBranchBulk($business_id, $branch_id, [$product_variation_id]);

        return (float) ($sums[$product_variation_id] ?? 0);
    }

    /**
     * Bulk form of getAvailableStockForBranch() - one pair of grouped queries
     * (batch-tracked variations, plain variations) instead of one query per
     * variation, for product listing/detail pages.
     */
    public function getAvailableStockForBranchBulk($business_id, $branch_id, array $product_variation_ids): array
    {
        $product_variation_ids = array_values(array_unique(array_filter($product_variation_ids)));

        if (empty($product_variation_ids)) {
            return [];
        }

        $warehouse_ids = $this->getLinkedWarehouseIds($business_id, $branch_id);

        if (empty($warehouse_ids)) {
            return [];
        }

        $variations = ProductVariation::whereIn('product_variation_id', $product_variation_ids)
            ->get(['product_variation_id', 'track_batch', 'track_expiry'])
            ->keyBy('product_variation_id');

        $batch_tracked_ids = $variations->filter(fn ($v) => $v->track_batch || $v->track_expiry)
            ->keys()->all();
        $plain_ids = array_values(array_diff($product_variation_ids, $batch_tracked_ids));

        $sums = [];

        if (!empty($plain_ids)) {
            $rows = ProductVariationStock::where('business_id', $business_id)
                ->whereIn('warehouse_id', $warehouse_ids)
                ->whereIn('product_variation_id', $plain_ids)
                ->selectRaw('product_variation_id, SUM(quantity - reserved_quantity) as qty')
                ->groupBy('product_variation_id')
                ->pluck('qty', 'product_variation_id');

            foreach ($rows as $variation_id => $qty) {
                $sums[$variation_id] = (float) $qty;
            }
        }

        if (!empty($batch_tracked_ids)) {
            $setting = InventorySetting::where('business_id', $business_id)->first();

            $batch_query = ProductVariationBatch::where('business_id', $business_id)
                ->whereIn('warehouse_id', $warehouse_ids)
                ->whereIn('product_variation_id', $batch_tracked_ids)
                ->where('status', Status::ACTIVE)
                ->where('quantity', '>', 0);

            if ($setting && $setting->enable_expiry_date && $setting->block_expired_sale) {
                $batch_query->where(function ($q) {
                    $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now()->toDateString());
                });
            }

            $batch_rows = $batch_query->selectRaw('product_variation_id, SUM(quantity) as qty')
                ->groupBy('product_variation_id')
                ->pluck('qty', 'product_variation_id');

            foreach ($batch_rows as $variation_id => $qty) {
                $sums[$variation_id] = (float) $qty;
            }
        }

        return $sums;
    }

    /**
     * Per-warehouse (and, for batch-tracked variations, per-batch) stock
     * breakdown for a branch - powers the "Stock: 7" click/hover detail UI
     * (POS, website, mobile). Same expired-batch exclusion rule as
     * getAvailableStockForBranch() so the breakdown always sums to the same
     * total shown next to it.
     */
    public function getStockBreakdownForBranch($business_id, $branch_id, $product_id, $product_variation_id): array
    {
        $warehouse_ids = $this->getLinkedWarehouseIds($business_id, $branch_id);

        if (empty($warehouse_ids)) {
            return [];
        }

        $warehouse_names = Warehouse::whereIn('warehouse_id', $warehouse_ids)->pluck('name', 'warehouse_id');
        $variation = ProductVariation::find($product_variation_id);
        $is_batch_tracked = $variation && ($variation->track_batch || $variation->track_expiry);

        if (!$is_batch_tracked) {
            return ProductVariationStock::where('business_id', $business_id)
                ->whereIn('warehouse_id', $warehouse_ids)
                ->where('product_id', $product_id)
                ->where('product_variation_id', $product_variation_id)
                ->get()
                ->map(function ($stock) use ($warehouse_names) {
                    return [
                        'warehouse_id' => $stock->warehouse_id,
                        'warehouse_name' => $warehouse_names[$stock->warehouse_id] ?? '-',
                        'quantity' => (float) $stock->quantity - (float) $stock->reserved_quantity,
                        'batches' => [],
                    ];
                })
                ->values()
                ->all();
        }

        $setting = InventorySetting::where('business_id', $business_id)->first();

        $batch_query = ProductVariationBatch::where('business_id', $business_id)
            ->whereIn('warehouse_id', $warehouse_ids)
            ->where('product_id', $product_id)
            ->where('product_variation_id', $product_variation_id)
            ->where('status', Status::ACTIVE)
            ->where('quantity', '>', 0);

        if ($setting && $setting->enable_expiry_date && $setting->block_expired_sale) {
            $batch_query->where(function ($q) {
                $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now()->toDateString());
            });
        }

        $by_warehouse = [];

        foreach ($batch_query->orderBy('expiry_date')->get() as $batch) {
            $entry = &$by_warehouse[$batch->warehouse_id];
            $entry['warehouse_id'] = $batch->warehouse_id;
            $entry['warehouse_name'] = $warehouse_names[$batch->warehouse_id] ?? '-';
            $entry['quantity'] = ($entry['quantity'] ?? 0) + (float) $batch->quantity;
            $entry['batches'][] = [
                'batch_no' => $batch->batch_no,
                'quantity' => (float) $batch->quantity,
                'expiry_date' => $batch->expiry_date,
            ];
            unset($entry);
        }

        return array_values($by_warehouse);
    }
}
