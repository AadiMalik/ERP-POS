<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\ProductVariationUnitConversionService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\UnitService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductVariationUnitConversionController extends Controller
{
    use ResponseAPI;

    protected $business_service;
    protected $product_service;
    protected $unit_service;
    protected $product_variation_unit_conversion_service;

    public function __construct(
        BusinessService $business_service,
        ProductService $product_service,
        UnitService $unit_service,
        ProductVariationUnitConversionService $product_variation_unit_conversion_service
    ) {
        $this->business_service = $business_service;
        $this->product_service = $product_service;
        $this->unit_service = $unit_service;
        $this->product_variation_unit_conversion_service = $product_variation_unit_conversion_service;
    }

    public function index()
    {
        $business =  $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $units = $this->unit_service->getAllActive();
        return view('admin.variation_unit_conversion.index', compact('business','products','units'));
    }

    public function getData(Request $request)
    {
        return $this->product_variation_unit_conversion_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'product_variation_unit_conversion_id' => 'nullable|exists:product_variation_unit_conversions,product_variation_unit_conversion_id',
            'product_id' => 'required|exists:products,product_id',
            'product_variation_id' => 'required|exists:product_variations,product_variation_id',
            'from_unit_id' => 'required|exists:units,unit_id',
            'to_unit_id' => 'required|exists:units,unit_id',
            'conversion_factor' => 'required|numeric',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }


        $obj = $request->only([
            'product_variation_unit_conversion_id',
            'product_id',
            'product_variation_id',
            'from_unit_id',
            'to_unit_id',
            'conversion_factor',
        ]);
        $obj['business_id'] = $request->business_id ??  Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        // create/update product variation unit conversion
        $product_variation_unit_conversion = $this->product_variation_unit_conversion_service->save($obj);
        return $this->success(
            empty($request->product_variation_unit_conversion_id) ? Message::SAVE : Message::UPDATE,
            $product_variation_unit_conversion
        );
    }
    public function edit($product_variation_unit_conversion_id)
    {
        try {
            $product_variation_unit_conversion = $this->product_variation_unit_conversion_service->getById($product_variation_unit_conversion_id);
            return $this->success(
                Message::FETCH,
                $product_variation_unit_conversion
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($product_variation_unit_conversion_id)
    {
        try {
            $this->product_variation_unit_conversion_service->status($product_variation_unit_conversion_id);
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

    public function destroy($product_variation_unit_conversion_id)
    {
        try {

            $this->product_variation_unit_conversion_service->delete($product_variation_unit_conversion_id);

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

    public function byBusiness($business_id)
    {
        try {
            $product_variation_unit_conversion = $this->product_variation_unit_conversion_service->getByBusiness($business_id);
            return $this->success(
                Message::SUCCESS,
                $product_variation_unit_conversion
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
            $product_variation_unit_conversion = $this->product_variation_unit_conversion_service->getByProduct($product_id);
            return $this->success(
                Message::SUCCESS,
                $product_variation_unit_conversion
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
            $product_variation_unit_conversion = $this->product_variation_unit_conversion_service->getByProductVariation($product_variation_id);
            return $this->success(
                Message::SUCCESS,
                $product_variation_unit_conversion
            );
        } catch (Exception $e) {
            return $this->error(
                $e->getMessage()
                // Message::ERROR
            );
        }
    }
}
