<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Concerns\HandlesImportExport;
use App\Http\Controllers\Controller;
use App\Models\ProductVariation;
use App\Services\Concrete\Admin\BarcodeService;
use App\Services\Concrete\Admin\BrandService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CategoryService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\SaleTypeService;
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
    use HandlesImportExport;

    protected $product_service;
    protected $business_service;
    protected $category_service;
    protected $brand_service;
    protected $unit_service;
    protected $barcode_service;
    protected $sale_type_service;

    public function __construct(
        ProductService $product_service,
        BusinessService $business_service,
        CategoryService $category_service,
        BrandService $brand_service,
        UnitService $unit_service,
        BarcodeService $barcode_service,
        SaleTypeService $sale_type_service
    ) {
        $this->middleware('permission:product.view')->only(['index', 'getData', 'byBusiness', 'byBrand', 'byCategory', 'variations', 'byProduct', 'getImages', 'variationPriceHistory']);
        $this->middleware('permission:product.create')->only(['create', 'uploadImages']);
        $this->middleware('permission:product.create|product.edit')->only(['store']);
        $this->middleware('permission:product.edit')->only(['edit', 'variationStatus', 'setDefaultImage', 'saveImageSorting', 'backfillBarcodes']);
        $this->middleware('permission:product.delete')->only(['destroy', 'variationDestroy', 'deleteImage']);
        $this->middleware('permission:product.status')->only(['status']);
        $this->middleware('permission:product.import')->only(['importSample', 'importPreview', 'importConfirm']);
        $this->middleware('permission:product.export')->only(['export']);
        $this->middleware('module:product');

        $this->product_service = $product_service;
        $this->business_service = $business_service;
        $this->category_service = $category_service;
        $this->brand_service = $brand_service;
        $this->unit_service = $unit_service;
        $this->barcode_service = $barcode_service;
        $this->sale_type_service = $sale_type_service;
    }

    protected function importExportModuleKey(): string
    {
        return 'product';
    }

    public function index()
    {
        $businesses = $this->business_service->getAllActive();
        $categories = $this->category_service->getAllActive();
        $brands = $this->brand_service->getAllActive();
        return view('admin.product.index', compact('businesses', 'categories', 'brands'));
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
        $sale_types = $this->sale_type_service->getAllActive();
        return view('admin.product.create', compact('businesses', 'categories', 'brands', 'units', 'sale_types'));
    }

    public function store(Request $request)
    {

        $variations = collect($request->variations ?? [])
            ->map(function ($item) {
                return is_string($item)
                    ? json_decode($item, true)
                    : $item;
            })
            ->toArray();

        $features = collect($request->features ?? [])
            ->map(function ($item) {
                return is_string($item)
                    ? json_decode($item, true)
                    : $item;
            })
            ->toArray();

        $request->merge([
            'variations' => $variations,
            'features' => $features
        ]);

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
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'variations' => 'required|array|min:1',
            'variations.*.name' => 'required',
            'variations.*.sku' => 'required',
            'variations.*.barcode' => 'nullable',
            'variations.*.base_unit_id' => 'required|exists:units,unit_id',
            'variations.*.purchase_unit_id' => 'required|exists:units,unit_id',
            'variations.*.sale_unit_id' => 'required|exists:units,unit_id',
            'variations.*.purchase_price' => 'nullable|min:0',
            'variations.*.sale_price' => 'required|min:0',
            'variations.*.minimum_stock' => 'nullable|min:0',
            'variations.*.minimum_selling_price' => 'nullable|numeric|min:0',
            'variations.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'variations.*.discount_apply_all' => 'nullable|boolean',
            'variations.*.discount_sale_type_ids' => 'nullable|array',
            'variations.*.discount_sale_type_ids.*' => 'exists:sale_types,sale_type_id',
            'variations.*.prices' => 'nullable|array',
            'variations.*.prices.*' => 'nullable|numeric|min:0',
            'features' => 'nullable|array',
            'features.*.name' => 'required_with:features',
            'features.*.description' => 'required_with:features',
        ];

        foreach ($variations as $index => $variation) {
            if (!empty($variation['barcode'])) {
                $rules["variations.$index.barcode"] = [
                    Rule::unique('product_variations', 'barcode')
                        ->where(function ($query) use ($request) {
                            return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                                ->where('is_deleted', 0);
                        })
                        ->ignore($variation['product_variation_id'] ?? null, 'product_variation_id'),
                ];
            }
        }

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        // Build product data
        $obj = [
            'product_id' => $request->product_id,
            'name' => $request->name,
            'slug' => $request->slug,
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id,
            'sub_category_id' => $request->sub_category_id,
            'type' => $request->type,
            'usage_type' => $request->usage_type,
            'is_track_stock' => $request->has('is_track_stock') ? 1 : 0,
            'is_pos_visible' => $request->has('is_pos_visible') ? 1 : 0,
            'is_website_visible' => $request->has('is_website_visible') ? 1 : 0,
            'is_app_visible' => $request->has('is_app_visible') ? 1 : 0,
            'is_featured' => $request->has('is_featured') ? 1 : 0,
            'short_description' => $request->short_description,
            'description' => $request->description,
            'features' => $features,
            'variations' => $variations,
            'business_id' => $request->business_id ?? Auth::user()->business_id,
            'status' => $request->status ?? 'active',
        ];

        // Create/update product
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
        $sale_types = $this->sale_type_service->getAllActive();
        return view('admin.product.create', compact('product', 'businesses', 'categories', 'brands', 'units', 'sale_types'));
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

    public function variations($product_id)
    {
        try {
            $variations = $this->product_service->getVariations($product_id);
            $html = view('admin.product.partials.variations', compact('variations', 'product_id'))->render();

            return $this->success(Message::SUCCESS, $html);
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function byProduct($product_id)
    {
        try {
            $variations = $this->product_service->getVariations($product_id);
            return $this->success(Message::SUCCESS, $variations);
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function variationPriceHistory($product_variation_id)
    {
        try {
            $history = $this->product_service->getVariationPriceHistory($product_variation_id);
            return $this->success(Message::SUCCESS, $history);
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function variationStatus($product_variation_id)
    {
        try {
            $this->product_service->getVariations($product_variation_id);
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

    public function variationDestroy($product_variation_id)
    {
        try {
            $this->product_service->variationDelete($product_variation_id);
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

    public function backfillBarcodes(Request $request)
    {
        try {
            $businessId = $request->business_id ?? Auth::user()->business_id;

            $variations = ProductVariation::where('business_id', $businessId)
                ->where('is_deleted', 0)
                ->where(function ($q) {
                    $q->whereNull('barcode')->orWhere('barcode', '');
                })
                ->get();

            foreach ($variations as $variation) {
                $this->barcode_service->generateForVariation($variation, null, null, false);
            }

            return $this->success(Message::SUCCESS, ['count' => $variations->count()]);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    // product images
    public function getImages($product_id)
    {
        $images = $this->product_service->getImages($product_id);

        $html = view('admin.product.partials.images', compact('images'))->render();

        return $this->success(Message::SUCCESS, $html);
    }
    public function uploadImages(Request $request)
    {
        $rules = [
            'product_id' => 'required|exists:products,product_id',
            'images'      => 'required|array|min:1',
            'images.*'    => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'set_default' => 'nullable|boolean',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->product_service->uploadImages(
                $request->product_id,
                $request->file('images'),
                $request->set_default
            );
            return $this->success(
                Message::SAVE,
                []
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
    public function setDefaultImage($id)
    {
        try {
            $this->product_service->setDefault($id);
            return $this->success(
                Message::SAVE,
                []
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function saveImageSorting(Request $request)
    {
        try {
            $request->validate(['order' => 'required|array']);
            $this->product_service->saveSorting($request->order);
            return $this->success(
                Message::SAVE,
                []
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function deleteImage($product_image_id)
    {
        try {
            $this->product_service->deleteImage($product_image_id);
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
