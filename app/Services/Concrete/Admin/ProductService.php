<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Product;
use App\Models\ProductFeature;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\ProductVariationAttribute;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class ProductService
{
    protected $model_product;
    protected $model_product_image;
    protected $model_product_feature;
    protected $model_product_variation;
    protected $model_product_variation_attribute;
    protected $with = [
        'business',
        'category',
        'subCategory',
        'brand',
        'productImages',
        'productVariations',
        'productFeatures'
    ];

    public function __construct()
    {
        $this->model_product = new Repository(new Product());
        $this->model_product_image = new Repository(new ProductImage());
        $this->model_product_feature = new Repository(new ProductFeature());
        $this->model_product_variation = new Repository(new ProductVariation());
        $this->model_product_variation_attribute = new Repository(new ProductVariationAttribute());
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
        if (isset($obj['brand_id']) && $obj['brand_id'] != 0 && $obj['brand_id'] != "") {
            $wh[] = ['brand_id', $obj['brand_id']];
        }
        if (isset($obj['category_id']) && $obj['category_id'] != 0 && $obj['category_id'] != "") {
            $wh[] = ['category_id', $obj['category_id']];
        }
        if (isset($obj['sub_category_id']) && $obj['sub_category_id'] != 0 && $obj['sub_category_id'] != "") {
            $wh[] = ['sub_category_id', $obj['sub_category_id']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }

        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];
        $datatable = $this->model_product->getModel()::where($wh)
            ->with($this->with)
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->addColumn('category', function ($item) {
                return $item->category->name ?? '';
            })
            ->addColumn('brand', function ($item) {
                return $item->brand->name ?? '';
            })
            ->addColumn('images', function ($item) {
                return view('admin.product.partials.images', compact('item'))->render();
            })
            ->addColumn('variations', function ($item) {
                $count = $item->productVariations->count();

                return '
                    <button class="btn btn-sm btn-primary view-variations"
                        data-id="' . $item->product_id . '">
                        Variations <span class="badge bg-light text-dark">' . $count . '</span>
                    </button>
                ';
            })

            ->addColumn('features', function ($item) {
                return '<button class="btn btn-sm btn-secondary view-features" data-id="' . $item->product_id . '">
                        Features (' . count($item->productFeatures) . ')
                    </button>';
            })
            ->addColumn('status', function ($item) {

                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusProduct"
                        type="checkbox"
                        data-id="' . $item->product_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('product.edit', $item->product_id) . "'
                    id='editProduct'>

                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteProdct'
                    data-id='{$item->product_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['category', 'brand', 'images', 'variations', 'features', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {

            // =========================
            // UPDATE
            // =========================
            if (!empty($obj['product_id'])) {

                $product = $this->model_product->find($obj['product_id']);

                if (!$product) {
                    throw new Exception('Product not found');
                }

                $obj['updatedby_id'] = Auth::id();
                $obj['date_updated'] = now();


                $features = $obj['features'] ?? [];
                $variations = $obj['variations'] ?? [];

                unset($obj['features']);
                unset($obj['variations']);

                $this->model_product->update($obj, $obj['product_id']);

                // =========================
                // FEATURES
                // =========================
                $this->model_product_feature->getModel()::where('product_id', $product->product_id)->delete();

                foreach ($features as $feature) {

                    $this->model_product_feature->getModel()::create([
                        'product_feature_id' => generateUuid(),
                        'product_id' => $product->product_id,
                        'name' => $feature['name'],
                        'description' => $feature['description'],
                        'createdby_id' => Auth::id(),
                        'date_created' => now(),
                    ]);
                }
                // =========================
                // VARIATIONS
                // =========================

                // Existing active variations
                $existingVariationIds = $this->model_product_variation->getModel()::where(
                    'product_id',
                    $product->product_id
                )
                    ->where('is_deleted', 0)
                    ->pluck('product_variation_id')
                    ->toArray();

                $requestVariationIds = [];

                foreach ($variations as $variation) {

                    // =========================
                    // UPDATE
                    // =========================
                    if (!empty($variation['product_variation_id'])) {

                        $requestVariationIds[] = $variation['product_variation_id'];

                        $this->model_product_variation->getModel()::where(
                            'product_variation_id',
                            $variation['product_variation_id']
                        )->update([
                            'name' => $variation['name'],
                            'sku' => $variation['sku'],
                            'barcode' => $variation['barcode'],
                            'base_unit_id' => $variation['base_unit_id'],
                            'purchase_price' => $variation['purchase_price'],
                            'sale_price' => $variation['sale_price'],
                            'minimum_stock' => $variation['minimum_stock'],
                            'business_unit_id' => $obj['business_id'],
                            'updatedby_id' => Auth::id(),
                            'date_updated' => now(),
                        ]);

                        $product_variation_id = $variation['product_variation_id'];
                    }

                    // =========================
                    // CREATE
                    // =========================
                    else {

                        $product_variation_id = generateUuid();

                        $this->model_product_variation->getModel()::create([
                            'product_variation_id' => $product_variation_id,
                            'product_id' => $product->product_id,
                            'name' => $variation['name'],
                            'sku' => $variation['sku'],
                            'barcode' => $variation['barcode'],
                            'base_unit_id' => $variation['base_unit_id'],
                            'purchase_price' => $variation['purchase_price'],
                            'sale_price' => $variation['sale_price'],
                            'minimum_stock' => $variation['minimum_stock'],
                            'createdby_id' => Auth::id(),
                            'date_created' => now(),
                        ]);

                        $requestVariationIds[] = $product_variation_id;
                    }

                    // =========================
                    // ATTRIBUTES
                    // =========================

                    $this->model_product_variation_attribute->getModel()::where(
                        'product_variation_id',
                        $product_variation_id
                    )->delete();

                    foreach (($variation['attributes'] ?? []) as $name => $value) {

                        $this->model_product_variation_attribute->getModel()::create([
                            'product_variation_attribute_id' => generateUuid(),
                            'product_variation_id' => $product_variation_id,
                            'name' => $name,
                            'value' => $value,
                            'createdby_id' => Auth::id(),
                            'date_created' => now(),
                        ]);
                    }
                }

                $deletedVariationIds = array_diff(
                    $existingVariationIds,
                    $requestVariationIds
                );

                if (!empty($deletedVariationIds)) {

                    $this->model_product_variation->getModel()::whereIn(
                        'product_variation_id',
                        $deletedVariationIds
                    )->update([
                        'is_deleted' => 1,
                        'deletedby_id' => Auth::id(),
                        'date_deleted' => now(),
                    ]);

                    $this->model_product_variation_attribute->getModel()::whereIn(
                        'product_variation_id',
                        $deletedVariationIds
                    )->delete();
                }
                // =========================    

                DB::commit();

                return $this->model_product->find($product->product_id);
            }

            //check limit
            $limit = checkPackageLimit('products');

            if (!$limit['status']) {
                throw new Exception($limit['message']);
            }
            // =========================
            // CREATE
            // =========================

            $features = $obj['features'] ?? [];
            $variations = $obj['variations'] ?? [];

            unset($obj['features']);
            unset($obj['variations']);

            $obj['product_id'] = generateUuid();
            $obj['createdby_id'] = Auth::id();
            $obj['date_created'] = now();

            $product = $this->model_product->create($obj);

            // Features
            foreach ($features as $feature) {

                $this->model_product_feature->getModel()::create([
                    'product_feature_id' => generateUuid(),
                    'product_id' => $product->product_id,
                    'name' => $feature['name'],
                    'description' => $feature['description'],
                    'createdby_id' => Auth::id(),
                    'date_created' => now(),
                ]);
            }

            // Variations
            foreach ($variations as $variation) {

                $product_variation_id = generateUuid();
                $this->model_product_variation->getModel()::create([
                    'product_variation_id' => $product_variation_id,
                    'product_id' => $product->product_id,
                    'name' => $variation['name'],
                    'sku' => $variation['sku'],
                    'barcode' => $variation['barcode'],
                    'base_unit_id' => $variation['base_unit_id'],
                    'purchase_price' => $variation['purchase_price'],
                    'sale_price' => $variation['sale_price'],
                    'minimum_stock' => $variation['minimum_stock'],
                    'business_unit_id' => $obj['business_id'],
                    'createdby_id' => Auth::id(),
                    'date_created' => now(),
                ]);

                foreach (($variation['attributes'] ?? []) as $name => $value) {

                    $this->model_product_variation_attribute->getModel()::create([
                        'product_variation_attribute_id' => generateUuid(),
                        'product_variation_id' => $product_variation_id,
                        'name' => $name,
                        'value' => $value,
                        'createdby_id' => Auth::id(),
                        'date_created' => now(),
                    ]);
                }
            }

            DB::commit();

            return $product;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($product_id)
    {
        return $this->model_product->find($product_id);
    }
    public function status($product_id)
    {
        return $this->model_product->update([
            'status' => ($this->model_product->find($product_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $product_id);
    }

    public function delete($product_id)
    {
        return $this->model_product->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $product_id);
    }

    public function getAll()
    {
        return $this->model_product->getModel()::with($this->with)
            ->where('business_id', Auth::user()->business_id)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getAllActive()
    {
        return $this->model_product->getModel()::with($this->with)
            ->where('business_id', Auth::user()->business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getByBusiness($business_id)
    {
        return $this->model_product->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getByBrand($brand_id)
    {
        return $this->model_product->getModel()::with($this->with)
            ->where('brand_id', $brand_id)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getByCategory($category_id)
    {
        return $this->model_product->getModel()::with('business')
            ->where('category_id', $category_id)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getVariations($product_id)
    {
        return $this->model_product_variation->getModel()::with('attributes')
            ->where('product_id', $product_id)
            ->where('is_deleted', 0)
            ->get();
    }

    public function variationStatus($product_variation_id)
    {
        return $this->model_product_variation->update([
            'status' => ($this->model_product->find($product_variation_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $product_variation_id);
    }

    public function variationDelete($product_variation_id)
    {
        return $this->model_product_variation->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $product_variation_id);
    }
}
