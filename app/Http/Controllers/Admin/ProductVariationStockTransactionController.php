<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\ProductVariationStockTransactionService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Services\Concrete\Admin\VariationBatchService;
use Exception;
use Illuminate\Http\Request;
use App\Traits\ResponseAPI;
use App\Enums\TransactionType;
use App\Enums\ReferenceType;
use App\Services\Concrete\Admin\ProductVariationBatchService;
use App\Services\Concrete\Admin\UnitService;

class ProductVariationStockTransactionController extends Controller
{
    use ResponseAPI;
    protected $business_service;
    protected $product_service;
    protected $warehouse_service;
    protected $unit_service;
    protected $product_variation_batch_service;
    protected $product_variation_stock_transaction_service;

    public function __construct(
        BusinessService $business_service,
        ProductService $product_service,
        WarehouseService $warehouse_service,
        UnitService $unit_service,
        ProductVariationBatchService $product_variation_batch_service,
        ProductVariationStockTransactionService $product_variation_stock_transaction_service
    ) {
        $this->middleware('permission:stock-transaction.view');

        $this->business_service = $business_service;
        $this->product_service = $product_service;
        $this->warehouse_service = $warehouse_service;
        $this->unit_service = $unit_service;
        $this->product_variation_batch_service = $product_variation_batch_service;
        $this->product_variation_stock_transaction_service = $product_variation_stock_transaction_service;
    }

    public function index()
    {
        $business =  $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $warehouse = $this->warehouse_service->getAllActive();
        $units = $this->unit_service->getAllActive();
        $transaction_types = TransactionType::getoptions();
        $reference_types = ReferenceType::getoptions();
        $batches = $this->product_variation_batch_service->getAllActive();
        return view('admin.variation_stock_transaction.index', compact('business', 'batches', 'products', 'warehouse', 'units', 'transaction_types', 'reference_types'));
    }

    public function getData(Request $request)
    {
        return $this->product_variation_stock_transaction_service->getData($request->all());
    }
    public function destroy($product_variation_stock_transaction_id)
    {
        try {

            $this->product_variation_stock_transaction_service->delete($product_variation_stock_transaction_id);

            return $this->success(
                Message::DELETE,
                []
            );
        } catch (Exception $e) {

            return $this->error(
                Message::ERROR
            );
        }
    }
}
