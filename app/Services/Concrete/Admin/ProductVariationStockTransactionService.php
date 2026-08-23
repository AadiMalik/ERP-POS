<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\ProductVariationStockTransaction;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class ProductVariationStockTransactionService
{
    use Auditable;

    protected $model_product_variation_stock_transaction;
    protected $with = ['business', 'product', 'productVariation', 'batch', 'warehouse', 'productVariationBatch', 'unit', 'createdBy', 'updatedBy', 'deletedBy'];
    protected $product_variation_stock_service;

    public function __construct(ProductVariationStockService $product_variation_stock_service)
    {
        $this->model_product_variation_stock_transaction = new Repository(new ProductVariationStockTransaction());
        $this->product_variation_stock_service = $product_variation_stock_service;
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
        if (!empty($obj['transaction_type'])) {
            $wh[] = ['transaction_type', $obj['transaction_type']];
        }
        if (!empty($obj['reference_type'])) {
            $wh[] = ['reference_type', $obj['reference_type']];
        }
        if (!empty($obj['unit_id'])) {
            $wh[] = ['unit_id', $obj['unit_id']];
        }
        if (!empty($obj['product_variation_batch_id'])) {
            $wh[] = ['product_variation_batch_id', $obj['product_variation_batch_id']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['transaction_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['transaction_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];
        $datatable = $this->model_product_variation_stock_transaction->getModel()::where($wh)
            ->with($this->with)
            ->where('is_deleted', 0)
            ->orderBy('transaction_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('transaction_date', function ($item) {
                return date('d-m-Y H:i', strtotime($item->transaction_date));
            })
            ->addColumn('product', function ($item) {

                return $item->product?->name ?? '-';
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
            ->addColumn('unit', function ($item) {
                return $item->unit?->name ?? '-';
            })
            ->addColumn('batch', function ($item) {
                return $item->productVariationBatch?->batch_no ?? '-';
            })
            ->addColumn('transaction_type', function ($item) {

                return ucfirst($item->transaction_type);
            })
            ->addColumn('quantity', function ($item) {

                return number_format($item->quantity, 2);
            })

            ->addColumn('unit_price', function ($item) {

                return number_format($item->unit_price, 2);
            })
            ->addColumn('total_price', function ($item) {

                return number_format($item->total_price, 2);
            })

            ->addColumn('balance_qty', function ($item) {

                return number_format($item->quantity_after, 2);
            })

            ->addColumn('avg_cost', function ($item) {

                return number_format($item->avg_price_after, 2);
            })

            ->addColumn('reference', function ($item) {

                return $item->reference_type . ' #' . $item->reference_id;
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteProductVariationStockTransaction'
                    data-id='{$item->product_variation_stock_transaction_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns([
                'business',
                'product',
                'productVariation',
                'warehouse',
                'unit',
                'batch',
                'reference',
                'transaction_date',
                'transaction_type',
                'quantity',
                'unit_price',
                'total_price',
                'balance_qty',
                'avg_cost',
                'action'
            ])
            ->make(true);
    }

    /**
     * Reverse a single stock transaction rather than leaving inventory
     * totals inconsistent: soft-deletes the row, then replays the remaining
     * active ledger for that product+variation+warehouse via
     * ProductVariationStockService::recomputeLedger() so the aggregate Stock
     * row and every later transaction's stored running-balance snapshot stay
     * correct - mirrors the reversal pattern already used by
     * PurchaseReturnService/OrderService/GrnService/StockTakingService.
     * $reason is mandatory and kept for audit (distinct from `remarks`,
     * which records why the transaction was originally created).
     */
    public function delete($product_variation_stock_transaction_id, string $reason)
    {
        if (trim($reason) === '') {
            throw new Exception('A reason is required to delete/reverse a stock transaction.');
        }

        DB::beginTransaction();

        try {
            $transaction = $this->model_product_variation_stock_transaction->getModel()::where('is_deleted', 0)
                ->findOrFail($product_variation_stock_transaction_id);

            $before = $transaction->only(['quantity_after', 'avg_price_after']);

            $transaction->update([
                'is_deleted' => 1,
                'delete_reason' => $reason,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            $after = $this->product_variation_stock_service->recomputeLedger(
                $transaction->business_id,
                $transaction->warehouse_id,
                $transaction->product_id,
                $transaction->product_variation_id
            );

            $this->logActivity(
                'stock-transaction',
                $product_variation_stock_transaction_id,
                'reversed',
                $before,
                $after,
                $reason,
                $transaction->business_id
            );

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function getAll()
    {
        return $this->model_product_variation_stock_transaction->getModel()::with($this->with)
            ->where('business_id', Auth::user()->business_id)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getByProduct($product_id)
    {
        return $this->model_product_variation_stock_transaction->getModel()::with($this->with)
            ->where('product_id', $product_id)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getByProductVariation($product_variation_id)
    {
        return $this->model_product_variation_stock_transaction->getModel()::with($this->with)
            ->where('product_variation_id', $product_variation_id)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getByWarehouse($warehouse_id)
    {
        return $this->model_product_variation_stock_transaction->getModel()::with($this->with)
            ->where('warehouse_id', $warehouse_id)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getByBusiness($business_id)
    {
        return $this->model_product_variation_stock_transaction->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
