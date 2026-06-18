<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BrandService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CategoryService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\UnitService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use ResponseAPI;

    protected $product_service;
    protected $business_service;
    protected $category_service;
    protected $brand_service;
    protected $unit_service;

    public function __construct(
        ProductService $product_service,
        BusinessService $business_service,
        CategoryService $category_service,
        BrandService $brand_service,
        UnitService $unit_service
    ) {
        $this->product_service = $product_service;
        $this->business_service = $business_service;
        $this->category_service = $category_service;
        $this->brand_service = $brand_service;
        $this->unit_service = $unit_service;
    }
    public function index()
    {
        $businesses = $this->business_service->getAllActive();
        $categories = $this->category_service->getAllActive();
        $brands = $this->brand_service->getAllActive();
        $units = $this->unit_service->getAllActive();
        return view('admin.product.index', compact('businesses', 'categories', 'brands', 'units'));
    }
    public function getData(Request $request)
    {
        return $this->product_service->getData($request->all());
    }
    public function create()
    {
        $businesses = $this->business_service->getAllActive();
        $categories = $this->category_service->getAllActive();
        $brands = $this->brand_service->getAllActive();
        $units = $this->unit_service->getAllActive();
        return view('admin.product.create', compact('businesses', 'categories', 'brands', 'units'));
    }

    public function store(Request $request)
    {
        dd($request->all());
        $rules = [
            'name' => [
                'required',
                Rule::unique('products', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->product_id, 'product_id')
            ],
            'slug' => [
                'required',
                Rule::unique('products', 'slug')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->product_id, 'product_id')
            ],
            'category_id' => 'required|exists:categories,category_id',
            'brand_id' => 'required|exists:brands,brand_id',
            'sub_category_id' => 'nullable|exists:sub_categories,sub_category_id',
            'type' => 'required|in:single,variable,service',
            'usage_type' => 'required|in:saleable,consumable,asset,service',
            'images' => 'required|array|min:1',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'variations' => 'required|array|min:1',
            'variations.*.name' => 'required',
            'variations.*.price' => 'required',
            'variations.*.sku' => 'required',
            'variations.*.barcode' => 'required',
            'variations.*.base_unit_id' => 'required|exists:units,unit_id',
            'variations.*.purchase_price' => 'required|min:0',
            'variations.*.sale_price' => 'required|min:0',
            'variations.*.minimum_stock' => 'required|min:0',
        ];

        if (!empty($request->is_featured) && $request->is_featured == 'on') {
            $rules['features'] = 'required|array|min:1';
            $rules['features.*.name'] = 'required';
            $rules['features.*.value'] = 'required';
        }

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }


        $obj = $request->only([
            'product_id',
            'name',
            'slug',
            'category_id',
            'brand_id',
            'sub_category_id',
            'type',
            'usage_type',
            'is_track_stock',
            'is_pos_visible',
            'is_website_visible',
            'is_app_visible',
            'is_featured',
            'short_description',
            'description',
            'features',
            'variations'
        ]);
        if ($request->hasFile('logo')) {

            $file = $request->file('logo');

            $fileName = time() . '_' . $file->getClientOriginalName();

            $file->move(public_path('uploads/product'), $fileName);

            $obj['logo'] = $fileName;
        }
        $obj['business_id'] = $request->business_id ??  Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        // create/update product
        $product = $this->product_service->save($obj);
        return redirect('admin/product')
            ->with('success', empty($request->product_id) ? Message::SAVE : Message::UPDATE);
    }
    public function edit($product_id)
    {
        $product = $this->product_service->getById($product_id);
        $businesses = $this->business_service->getAllActive();
        $categories = $this->category_service->getAllActive();
        $brands = $this->brand_service->getAllActive();
        $units = $this->unit_service->getAllActive();
        return view('admin.product.create', compact('product', 'businesses', 'categories', 'brands', 'units'));
    }

    public function status($product_id)
    {
        try {
            $this->product_service->status($product_id);
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

    public function destroy($product_id)
    {
        try {

            $this->product_service->delete($product_id);

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
            $products = $this->product_service->getByBusiness($business_id);
            return $this->success(
                Message::SUCCESS,
                $products
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function byBrand($brand_id)
    {
        try {
            $products = $this->product_service->getByBrand($brand_id);
            return $this->success(
                Message::SUCCESS,
                $products
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function byCategory($category_id)
    {
        try {
            $products = $this->product_service->getByCategory($category_id);
            return $this->success(
                Message::SUCCESS,
                $products
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
}
