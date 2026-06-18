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
    protected $with = ['business', 'category', 'sub_category', 'brand'];

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
            ->addColumn('status', function ($item) {

                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusBranch"
                        type="checkbox"
                        data-id="' . $item->branch_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('branch.edit', $item->branch_id) . "'
                    id='editBranch'>

                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteBranch'
                    data-id='{$item->branch_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['status', 'action'])
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

                $images = $obj['images'] ?? [];
                $features = $obj['features'] ?? [];
                $variations = $obj['variations'] ?? [];

                unset($obj['images']);
                unset($obj['features']);
                unset($obj['variations']);

                $this->model_product->update($obj, $obj['product_id']);

                // =========================
                // PRODUCT IMAGES
                // =========================
                if (!empty($images)) {

                    $this->model_product_image->getModel()::where('product_id', $product->product_id)->delete();

                    foreach ($images as $image) {

                        $this->model_product_image->getModel()::create([
                            'product_image_id' => generateUuid(),
                            'product_id' => $product->product_id,
                            'image' => $image['image'],
                            'is_default' => $image['is_default'] ?? 0,
                            'createdby_id' => Auth::id(),
                            'date_created' => now(),
                        ]);
                    }
                }

                // =========================
                // FEATURES
                // =========================
                $this->model_product_feature->getModel()::where('product_id', $product->product_id)->delete();

                foreach ($features as $feature) {

                    $this->model_product_feature->getModel()::create([
                        'product_feature_id' => generateUuid(),
                        'product_id' => $product->product_id,
                        'name' => $feature['name'],
                        'value' => $feature['value'],
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
                            'updatedby_id' => Auth::id(),
                            'date_updated' => now(),
                        ]);

                        $variationId = $variation['product_variation_id'];
                    }

                    // =========================
                    // CREATE
                    // =========================
                    else {

                        $variationId = generateUuid();

                        $this->model_product_variation->getModel()::create([
                            'product_variation_id' => $variationId,
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

                        $requestVariationIds[] = $variationId;
                    }

                    // =========================
                    // ATTRIBUTES
                    // =========================

                    $this->model_product_variation_attribute->getModel()::where(
                        'product_variation_id',
                        $variationId
                    )->delete();

                    foreach ($variation['attributes'] ?? [] as $attribute) {

                        $this->model_product_variation_attribute->getModel()::create([
                            'product_variation_attribute_id' => generateUuid(),
                            'product_variation_id' => $variationId,
                            'name' => $attribute['name'],
                            'value' => $attribute['value'],
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

            $images = $obj['images'] ?? [];
            $features = $obj['features'] ?? [];
            $variations = $obj['variations'] ?? [];

            unset($obj['images']);
            unset($obj['features']);
            unset($obj['variations']);

            $obj['product_id'] = generateUuid();
            $obj['createdby_id'] = Auth::id();
            $obj['date_created'] = now();

            $product = $this->model_product->create($obj);

            // Images
            foreach ($images as $image) {

                $this->model_product_image->getModel()::create([
                    'product_image_id' => generateUuid(),
                    'product_id' => $product->product_id,
                    'image' => $image['image'],
                    'is_default' => $image['is_default'] ?? 0,
                    'createdby_id' => Auth::id(),
                    'date_created' => now(),
                ]);
            }

            // Features
            foreach ($features as $feature) {

                $this->model_product_feature->getModel()::create([
                    'product_feature_id' => generateUuid(),
                    'product_id' => $product->product_id,
                    'name' => $feature['name'],
                    'value' => $feature['value'],
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
                    'createdby_id' => Auth::id(),
                    'date_created' => now(),
                ]);

                foreach ($variation['attributes'] as $attribute) {
                    $this->model_product_variation_attribute->getModel()::create([
                        'product_variation_attribute_id' => generateUuid(),
                        'product_variation_id' => $product_variation_id,
                        'name' => $attribute['name'],
                        'value' => $attribute['value'],
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
}
