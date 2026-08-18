<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\ProductVariationBatchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductVariationBatchController extends Controller
{
    use ResponseAPI;

    protected $business_service;
    protected $product_service;
    protected $warehouse_service;
    protected $product_variation_batch_service;

    public function __construct(
        BusinessService $business_service,
        ProductService $product_service,
        WarehouseService $warehouse_service,
        ProductVariationBatchService $product_variation_batch_service
    ) {
        $this->middleware('permission:batch.view')->only(['index', 'getData', 'byWarehouse', 'byBusiness', 'byProduct', 'byVariation']);
        $this->middleware('permission:batch.create|batch.edit')->only(['store']);
        $this->middleware('permission:batch.edit')->only(['edit']);
        $this->middleware('permission:batch.delete')->only(['destroy']);
        $this->middleware('permission:batch.status')->only(['status']);

        $this->business_service = $business_service;
        $this->product_service = $product_service;
        $this->warehouse_service = $warehouse_service;
        $this->product_variation_batch_service = $product_variation_batch_service;
    }

    public function index()
    {
        $business =  $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $warehouse = $this->warehouse_service->getAllActive();
        return view('admin.variation_batch.index', compact('business','products','warehouse'));
    }

    public function getData(Request $request)
    {
        return $this->product_variation_batch_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'product_variation_batch_id' => 'nullable|exists:product_variation_batches,product_variation_batch_id',
            'product_id' => 'required|exists:products,product_id',
            'product_variation_id' => 'required|exists:product_variations,product_variation_id',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'avg_price' => 'required',
            'quantity' => 'required',
            'manufacturing_date' => 'required',
            'expiry_date' => 'required',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }


        $obj = $request->only([
            'product_variation_batch_id',
            'product_id',
            'product_variation_id',
            'warehouse_id',
            'avg_price',
            'quantity',
            'manufacturing_date',
            'expiry_date',
        ]);
        $obj['business_id'] = $request->business_id ??  Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        // create/update product variation unit conversion
        $product_variation_batch = $this->product_variation_batch_service->save($obj);
        return $this->success(
            empty($request->product_variation_batch_id) ? Message::SAVE : Message::UPDATE,
            $product_variation_batch
        );
    }
    public function edit($product_variation_batch_id)
    {
        try {
            $product_variation_batch = $this->product_variation_batch_service->getById($product_variation_batch_id);
            return $this->success(
                Message::FETCH,
                $product_variation_batch
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($product_variation_batch_id)
    {
        try {
            $this->product_variation_batch_service->status($product_variation_batch_id);
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

    public function destroy($product_variation_batch_id)
    {
        try {

            $this->product_variation_batch_service->delete($product_variation_batch_id);

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

    public function byWarehouse($warehouse_id)
    {
        try {
            $product_variation_batch = $this->product_variation_batch_service->getByWarehouse($warehouse_id);
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
            $product_variation_batch = $this->product_variation_batch_service->getByBusiness($business_id);
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
            $product_variation_batch = $this->product_variation_batch_service->getByProduct($product_id);
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
            $product_variation_batch = $this->product_variation_batch_service->getByProductVariation($product_variation_id);
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
}
