<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\ReferenceType;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Models\GoodReceiptNote;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\ProductVariationStock;
use App\Models\ProductVariationStockTransaction;
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
}
