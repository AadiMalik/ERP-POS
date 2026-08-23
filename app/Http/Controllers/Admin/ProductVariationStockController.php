<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\ProductVariationStockService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\WarehouseService;
use Exception;
use Illuminate\Http\Request;
use App\Traits\ResponseAPI;

class ProductVariationStockController extends Controller
{
    use ResponseAPI;
    protected $business_service;
    protected $product_service;
    protected $warehouse_service;
    protected $product_variation_stock_service;

    public function __construct(
        BusinessService $business_service,
        ProductService $product_service,
        WarehouseService $warehouse_service,
        ProductVariationStockService $product_variation_stock_service
    ) {
        $this->middleware('permission:stock.view')->only(['index', 'getData', 'byWarehouse', 'byBusiness', 'byProduct', 'byVariation', 'history']);
        $this->middleware('permission:stock.status')->only(['status']);
        $this->middleware('permission:stock.delete')->only(['destroy']);

        $this->business_service = $business_service;
        $this->product_service = $product_service;
        $this->warehouse_service = $warehouse_service;
        $this->product_variation_stock_service = $product_variation_stock_service;
    }

    public function index()
    {
        $business =  $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $warehouse = $this->warehouse_service->getAllActive();
        return view('admin.variation_stock.index', compact('business', 'products', 'warehouse'));
    }

    public function getData(Request $request)
    {
        return $this->product_variation_stock_service->getData($request->all());
    }
    public function status($product_variation_stock_id)
    {
        try {
            $this->product_variation_stock_service->status($product_variation_stock_id);
            return $this->success(
                Message::STATUS,
                []
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function destroy($product_variation_stock_id)
    {
        try {

            $this->product_variation_stock_service->delete($product_variation_stock_id);

            return $this->success(
                Message::DELETE,
                []
            );
        } catch (Exception $e) {

            return $this->error(
                $e->getMessage()
            );
        }
    }

    public function byWarehouse($warehouse_id)
    {
        try {
            $product_variation_batch = $this->product_variation_stock_service->getByWarehouse($warehouse_id);
            return $this->success(
                Message::SUCCESS,
                $product_variation_batch
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
    public function byBusiness($business_id)
    {
        try {
            $product_variation_batch = $this->product_variation_stock_service->getByBusiness($business_id);
            return $this->success(
                Message::SUCCESS,
                $product_variation_batch
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function byProduct($product_id)
    {
        try {
            $product_variation_batch = $this->product_variation_stock_service->getByProduct($product_id);
            return $this->success(
                Message::SUCCESS,
                $product_variation_batch
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function byVariation($product_variation_id)
    {
        try {
            $product_variation_batch = $this->product_variation_stock_service->getByProductVariation($product_variation_id);
            return $this->success(
                Message::SUCCESS,
                $product_variation_batch
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function history($product_variation_stock_id)
    {
        try {
            $ledger = $this->product_variation_stock_service->getLedger($product_variation_stock_id);
            return $this->success(
                Message::SUCCESS,
                $ledger
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
}
