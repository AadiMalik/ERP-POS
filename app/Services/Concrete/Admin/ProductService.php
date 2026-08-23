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
use App\Models\ProductVariationPrice;
use App\Models\ProductVariationPriceHistory;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Yajra\DataTables\DataTables;

class ProductService
{
    use Auditable;

    protected $model_product;
    protected $model_product_image;
    protected $model_product_feature;
    protected $model_product_variation;
    protected $model_product_variation_attribute;
    protected $barcode_service;
    protected $with = [
        'business',
        'category',
        'subCategory',
        'brand',
        'productImages',
        'productVariations',
        'productVariations.unit:unit_id,name',
        'productVariations.purchaseUnit:unit_id,name',
        'productVariations.saleUnit:unit_id,name',
        'productVariations.attributes:product_variation_attribute_id,product_variation_id,name,value',
        'productVariations.prices',
        'productVariations.discountSaleTypes:sale_types.sale_type_id,name',
        'productFeatures'
    ];

    public function __construct(BarcodeService $barcode_service)
    {
        $this->model_product = new Repository(new Product());
        $this->model_product_image = new Repository(new ProductImage());
        $this->model_product_feature = new Repository(new ProductFeature());
        $this->model_product_variation = new Repository(new ProductVariation());
        $this->model_product_variation_attribute = new Repository(new ProductVariationAttribute());
        $this->barcode_service = $barcode_service;
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
                $images = $item->productImages->sortBy('sorting');
                $product_id = $item->product_id;
                return view('admin.product.partials.images', compact('images', 'product_id'))->render();
            })
            ->addColumn('variations', function ($item) {
                $count = $item->productVariations->count();

                return '
                    <a href="javascript:void(0)" class="badge bg-success me-1 view-variations"
                        data-id="' . $item->product_id . '">
                        Variations <span class="badge bg-light text-dark">' . $count . '</span>
                    </a>
                ';
            })

            ->addColumn('features', function ($item) {
                return '<span class="badge bg-primary me-1">
                        Features (' . count($item->productFeatures) . ')
                    </span>';
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

                        $old_pricing = $this->model_product_variation->getModel()::where(
                            'product_variation_id',
                            $variation['product_variation_id']
                        )->first(['purchase_price', 'sale_price', 'minimum_selling_price']);

                        $this->model_product_variation->getModel()::where(
                            'product_variation_id',
                            $variation['product_variation_id']
                        )->update([
                            'name' => $variation['name'],
                            'sku' => $variation['sku'],
                            // 'barcode' intentionally not set here - BarcodeService::generateForVariation()
                            // (called below) is the sole writer, so it can tell an untouched existing
                            // barcode apart from a newly submitted manual one and keep permanence intact.
                            'base_unit_id' => $variation['base_unit_id'],
                            'purchase_unit_id' => $variation['purchase_unit_id'],
                            'sale_unit_id' => $variation['sale_unit_id'],
                            'purchase_price' => $variation['purchase_price'],
                            'sale_price' => $variation['sale_price'],
                            'minimum_stock' => $variation['minimum_stock'],
                            'minimum_selling_price' => $variation['minimum_selling_price'] ?? null,
                            'discount_percentage' => $variation['discount_percentage'] ?? 0,
                            'discount_apply_all' => array_key_exists('discount_apply_all', $variation) ? (bool) $variation['discount_apply_all'] : true,
                            'business_id' => $obj['business_id'],
                            'updatedby_id' => Auth::id(),
                            'date_updated' => now(),
                        ]);

                        $product_variation_id = $variation['product_variation_id'];

                        $new_pricing = [
                            'purchase_price' => $variation['purchase_price'],
                            'sale_price' => $variation['sale_price'],
                            'minimum_selling_price' => $variation['minimum_selling_price'] ?? null,
                        ];
                        if ($old_pricing && $old_pricing->only(['purchase_price', 'sale_price', 'minimum_selling_price']) != $new_pricing) {
                            $this->logActivity(
                                'product',
                                $product_variation_id,
                                'price_changed',
                                $old_pricing->only(['purchase_price', 'sale_price', 'minimum_selling_price']),
                                $new_pricing,
                                'Variation pricing updated for product ' . $product->name
                            );
                        }

                        $this->barcode_service->generateForVariation(
                            $this->model_product_variation->getModel()::find($product_variation_id),
                            $variation['barcode'] ?? null,
                            $variation['barcode_type'] ?? null
                        );

                        $this->savePricingForVariation($product_variation_id, $obj['business_id'], $variation);
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
                            // 'barcode' intentionally not set here - BarcodeService::generateForVariation()
                            // (called below) is the sole writer, so it can tell an untouched existing
                            // barcode apart from a newly submitted manual one and keep permanence intact.
                            'base_unit_id' => $variation['base_unit_id'],
                            'purchase_unit_id' => $variation['purchase_unit_id'],
                            'sale_unit_id' => $variation['sale_unit_id'],
                            'purchase_price' => $variation['purchase_price'],
                            'sale_price' => $variation['sale_price'],
                            'minimum_stock' => $variation['minimum_stock'],
                            'minimum_selling_price' => $variation['minimum_selling_price'] ?? null,
                            'discount_percentage' => $variation['discount_percentage'] ?? 0,
                            'discount_apply_all' => array_key_exists('discount_apply_all', $variation) ? (bool) $variation['discount_apply_all'] : true,
                            'createdby_id' => Auth::id(),
                            'date_created' => now(),
                        ]);

                        $this->barcode_service->generateForVariation(
                            $this->model_product_variation->getModel()::find($product_variation_id),
                            $variation['barcode'] ?? null,
                            $variation['barcode_type'] ?? null
                        );

                        $this->savePricingForVariation($product_variation_id, $obj['business_id'], $variation);

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

                    ProductVariationPrice::whereIn('product_variation_id', $deletedVariationIds)->delete();
                    DB::table('product_variation_discount_sale_types')->whereIn('product_variation_id', $deletedVariationIds)->delete();
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
                    // 'barcode' intentionally not set here - see comment above in the update branch.
                    'base_unit_id' => $variation['base_unit_id'],
                    'purchase_unit_id' => $variation['purchase_unit_id'],
                    'sale_unit_id' => $variation['sale_unit_id'],
                    'purchase_price' => $variation['purchase_price'],
                    'sale_price' => $variation['sale_price'],
                    'minimum_stock' => $variation['minimum_stock'],
                    'minimum_selling_price' => $variation['minimum_selling_price'] ?? null,
                    'discount_percentage' => $variation['discount_percentage'] ?? 0,
                    'discount_apply_all' => array_key_exists('discount_apply_all', $variation) ? (bool) $variation['discount_apply_all'] : true,
                    'business_id' => $obj['business_id'],
                    'createdby_id' => Auth::id(),
                    'date_created' => now(),
                ]);

                $this->barcode_service->generateForVariation(
                    $this->model_product_variation->getModel()::find($product_variation_id),
                    $variation['barcode'] ?? null,
                    $variation['barcode_type'] ?? null
                );

                $this->savePricingForVariation($product_variation_id, $obj['business_id'], $variation);

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

    /**
     * Persists a variation's per-sale-type prices/minimum-selling-prices and
     * "Apply Discount On" sale types - both are delete-and-recreate child
     * rows (same pattern as ProductVariationAttribute), keyed off
     * $variation['prices'] (assoc array sale_type_id => ['price' => ...,
     * 'minimum_selling_price' => ...]) and $variation['discount_sale_type_ids']
     * (only read when discount_apply_all is false). Diffs old vs new price
     * per sale type and logs a ProductVariationPriceHistory row for each one
     * that actually changed, so price changes stay auditable. Minimum
     * selling price is not part of that history (its schema is
     * old_price/new_price-specific).
     */
    protected function savePricingForVariation($product_variation_id, $business_id, array $variation)
    {
        $prices = $variation['prices'] ?? [];

        $existing_prices = ProductVariationPrice::where('product_variation_id', $product_variation_id)
            ->pluck('price', 'sale_type_id');

        ProductVariationPrice::where('product_variation_id', $product_variation_id)->delete();

        foreach ($prices as $sale_type_id => $row) {
            $price = is_array($row) ? ($row['price'] ?? null) : $row;
            $minimum_selling_price = is_array($row) ? ($row['minimum_selling_price'] ?? null) : null;

            if ($sale_type_id === '' || $price === null || $price === '') {
                continue;
            }

            $price = (float) $price;
            $minimum_selling_price = ($minimum_selling_price !== null && $minimum_selling_price !== '')
                ? (float) $minimum_selling_price
                : null;

            ProductVariationPrice::create([
                'product_variation_price_id' => generateUuid(),
                'business_id' => $business_id,
                'product_variation_id' => $product_variation_id,
                'sale_type_id' => $sale_type_id,
                'price' => $price,
                'minimum_selling_price' => $minimum_selling_price,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);

            $old_price = $existing_prices->has($sale_type_id) ? (float) $existing_prices->get($sale_type_id) : null;

            if ($old_price !== null && abs($old_price - $price) < 0.0001) {
                continue;
            }

            ProductVariationPriceHistory::create([
                'product_variation_price_history_id' => generateUuid(),
                'business_id' => $business_id,
                'product_variation_id' => $product_variation_id,
                'sale_type_id' => $sale_type_id,
                'old_price' => $old_price,
                'new_price' => $price,
                'changedby_id' => Auth::id(),
                'date_created' => now(),
            ]);
        }

        DB::table('product_variation_discount_sale_types')->where('product_variation_id', $product_variation_id)->delete();

        $discount_apply_all = array_key_exists('discount_apply_all', $variation) ? (bool) $variation['discount_apply_all'] : true;

        if (!$discount_apply_all) {
            foreach (($variation['discount_sale_type_ids'] ?? []) as $sale_type_id) {
                if (empty($sale_type_id)) {
                    continue;
                }

                DB::table('product_variation_discount_sale_types')->insert([
                    'product_variation_id' => $product_variation_id,
                    'sale_type_id' => $sale_type_id,
                ]);
            }
        }
    }

    public function getById($product_id)
    {
        return $this->model_product->getModel()::with($this->with)->find($product_id);
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
        return $this->model_product->getModel()::with($this->with)
            ->where('category_id', $category_id)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getVariations($product_id)
    {
        return $this->model_product_variation->getModel()::with('unit:unit_id,name', 'purchaseUnit:unit_id,name', 'saleUnit:unit_id,name', 'attributes')
            ->where('product_id', $product_id)
            ->where('is_deleted', 0)
            ->get();
    }

    /**
     * Read-only audit trail for a variation's per-sale-type prices - rows are
     * written by savePricingForVariation() (and the import path's mirror of
     * it) whenever a price actually changes, so this just surfaces them.
     */
    public function getVariationPriceHistory($product_variation_id)
    {
        return ProductVariationPriceHistory::with('saleType:sale_type_id,name', 'changedby:id,name')
            ->where('product_variation_id', $product_variation_id)
            ->orderByDesc('date_created')
            ->get()
            ->map(function ($row) {
                return [
                    'date_created' => $row->date_created ? localDateTime($row->date_created) : '-',
                    'sale_type_name' => $row->saleType->name ?? '-',
                    'old_price' => $row->old_price !== null ? currency($row->old_price) : '-',
                    'new_price' => currency($row->new_price),
                    'changed_by' => $row->changedby->name ?? '-',
                ];
            });
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

    /**
     * Upload multiple images for a product.
     */
    public function uploadImages($product_id, array $files)
    {
        $uploaded  = [];

        // Current max sorting
        $max_sort = $this->model_product_image->getModel()::where('product_id', $product_id)->max('sorting') ?? 0;

        foreach ($files as $file) {
            $filename = generateUuid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/product'), $filename);

            $image = $this->model_product_image->getModel()::create([
                'product_image_id' => generateUuid(),
                'product_id'       => $product_id,
                'image'            => $filename,
                'sorting'          => ++$max_sort,
                'is_default'       => 0,
                'status'           => 1,
                'createdby_id'     => Auth::id(),
                'date_created'     => Carbon::now(),
            ]);

            $uploaded[] = $image;
            $is_first    = false;
        }

        return $uploaded;
    }

    /**
     * Delete a single product image.
     */
    public function deleteImage($product_image_id)
    {
        $image = ProductImage::findOrFail($product_image_id);

        $filePath = public_path('uploads/product/' . $image->image);
        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        return $image->delete();
    }

    /**
     * Set a specific image as default (clears others first).
     */
    public function setDefault($product_image_id)
    {
        $image = $this->model_product_image->getModel()::findOrFail($product_image_id);
        $this->clearDefault($image->product_id);
        $image->is_default = 1;
        return $image->save();
    }

    /**
     * Save new sort order. $order = [['id' => uuid, 'sorting' => int], ...]
     */
    public function saveSorting($order)
    {
        foreach ($order as $item) {
            $this->model_product_image->getModel()::where('product_image_id', $item['id'])
                ->update(['sorting' => $item['sorting']]);
        }
    }

    /**
     * Get all images for a product ordered by sorting.
     */
    public function getImages($product_id)
    {
        return $this->model_product_image->getModel()::where('product_id', $product_id)
            ->orderBy('sorting')
            ->get();
    }

    // ─────────────────────────────────────────────
    private function clearDefault($product_id)
    {
        $this->model_product_image->getModel()::where('product_id', $product_id)
            ->update(['is_default' => 0]);
    }
}
