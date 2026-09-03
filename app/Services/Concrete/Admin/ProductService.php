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
use App\Models\ProductVariationStock;
use App\Models\SaleType;
use App\Models\Warehouse;
use App\Repository\Repository;
use App\Services\Concrete\Api\WishlistService;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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
    protected $pricing_service;
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

    public function __construct(BarcodeService $barcode_service, VariationPricingService $pricing_service)
    {
        $this->model_product = new Repository(new Product());
        $this->model_product_image = new Repository(new ProductImage());
        $this->model_product_feature = new Repository(new ProductFeature());
        $this->model_product_variation = new Repository(new ProductVariation());
        $this->model_product_variation_attribute = new Repository(new ProductVariationAttribute());
        $this->barcode_service = $barcode_service;
        $this->pricing_service = $pricing_service;
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
                            'is_loyalty_enabled' => array_key_exists('is_loyalty_enabled', $variation) ? (bool) $variation['is_loyalty_enabled'] : false,
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
                            'is_loyalty_enabled' => array_key_exists('is_loyalty_enabled', $variation) ? (bool) $variation['is_loyalty_enabled'] : false,
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
                    'is_loyalty_enabled' => array_key_exists('is_loyalty_enabled', $variation) ? (bool) $variation['is_loyalty_enabled'] : false,
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

    // ─────────────────────────────────────────────
    // Public storefront (website) catalog
    // ─────────────────────────────────────────────

    /**
     * Storefront product listing for the Vue website - filterable/sortable/
     * paginated, plus (only when unfiltered on page 1) a set of curated
     * homepage sections. Correctness over raw SQL efficiency is the goal
     * here (small demo catalog): the base filters are applied in SQL, then
     * price/stock are resolved per variation and the rest (min/max price,
     * in_stock, sorting, pagination) happens in PHP against the mapped
     * summaries.
     */
    public function getWebsiteListing(string $business_id, array $params): array
    {
        $search = trim((string) ($params['search'] ?? ''));
        $category_id = $params['category_id'] ?? null;
        $sub_category_id = $params['sub_category_id'] ?? null;
        $brand_id = $params['brand_id'] ?? null;
        $min_price = (isset($params['min_price']) && $params['min_price'] !== '') ? (float) $params['min_price'] : null;
        $max_price = (isset($params['max_price']) && $params['max_price'] !== '') ? (float) $params['max_price'] : null;
        $in_stock = (isset($params['in_stock']) && $params['in_stock'] !== '' && $params['in_stock'] !== null)
            ? filter_var($params['in_stock'], FILTER_VALIDATE_BOOLEAN)
            : null;
        $on_sale = (isset($params['on_sale']) && $params['on_sale'] !== '' && $params['on_sale'] !== null)
            ? filter_var($params['on_sale'], FILTER_VALIDATE_BOOLEAN)
            : null;
        $sort = $params['sort'] ?? 'featured';
        $page = max(1, (int) ($params['page'] ?? 1));
        $per_page = min(100, max(1, (int) ($params['per_page'] ?? 24)));
        $branch_id = $params['branch_id'] ?? null;

        $has_filters = $search !== ''
            || !empty($category_id)
            || !empty($sub_category_id)
            || !empty($brand_id)
            || $min_price !== null
            || $max_price !== null
            || $in_stock !== null
            || $on_sale !== null;

        $query = $this->websiteBaseQuery($business_id)->with($this->websiteWith());

        if (!empty($category_id)) {
            $query->where('category_id', $category_id);
        }
        if (!empty($sub_category_id)) {
            $query->where('sub_category_id', $sub_category_id);
        }
        if (!empty($brand_id)) {
            $query->where('brand_id', $brand_id);
        }
        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $products = $query->get();

        $sale_type_id = $this->resolveDefaultSaleTypeId($business_id);

        [$price_map, $stock_map] = $this->resolvePricingAndStock($products, $business_id, $branch_id, $sale_type_id);

        $wishlist_flags = $this->resolveWishlistFlags(
            $business_id,
            $params['user_id'] ?? null,
            $products->pluck('product_id')->all()
        );

        // Resolved once per request (not per-product) so the "coin badge"
        // eligibility flag never re-queries CustomerSetting per row.
        $loyalty_context = $this->loyaltyEligibilityContext($business_id);

        $rows = $products->map(function ($product) use ($price_map, $stock_map, $wishlist_flags, $loyalty_context) {
            return [
                'product' => $product,
                'summary' => $this->mapProductSummary($product, $price_map, $stock_map, $wishlist_flags, $loyalty_context),
            ];
        });

        if ($min_price !== null) {
            $rows = $rows->filter(fn ($row) => $row['summary']['price'] >= $min_price);
        }
        if ($max_price !== null) {
            $rows = $rows->filter(fn ($row) => $row['summary']['price'] <= $max_price);
        }
        if ($in_stock !== null) {
            $rows = $rows->filter(function ($row) use ($in_stock) {
                $stock = $row['summary']['stock'];
                $is_in_stock = $stock === null || $stock > 0;
                return $in_stock ? $is_in_stock : !$is_in_stock;
            });
        }
        if ($on_sale !== null) {
            $rows = $rows->filter(function ($row) use ($on_sale) {
                $is_on_sale = $row['summary']['discount'] > 0;
                return $on_sale ? $is_on_sale : !$is_on_sale;
            });
        }

        $rows = $rows->values();

        $filters_meta = [
            'price_min' => $rows->isNotEmpty() ? $rows->min(fn ($row) => $row['summary']['price']) : null,
            'price_max' => $rows->isNotEmpty() ? $rows->max(fn ($row) => $row['summary']['price']) : null,
        ];

        $rows = $this->sortWebsiteRows($rows, $sort);

        $total = $rows->count();
        $last_page = max(1, (int) ceil($total / $per_page));
        $page = min($page, $last_page);

        $paged = $rows->slice(($page - 1) * $per_page, $per_page)->values();

        $sections = null;
        if (!$has_filters && $page == 1) {
            $sections = $this->buildWebsiteSections(
                $business_id,
                $branch_id,
                $sale_type_id,
                $params['user_id'] ?? null
            );
        }

        return [
            'sections' => $sections,
            'products' => [
                'data' => $paged->pluck('summary')->values()->all(),
                'current_page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'last_page' => $last_page,
            ],
            'filters_meta' => $filters_meta,
        ];
    }

    /**
     * Storefront single-product detail by slug. Returns null when not found
     * (caller/controller turns that into a 404).
     */
    public function getWebsiteDetail(string $business_id, string $slug, $user_id = null): ?array
    {
        $with = array_merge($this->websiteWith(), ['productFeatures']);

        $product = $this->websiteBaseQuery($business_id)
            ->where('slug', $slug)
            ->with($with)
            ->first();

        if (!$product) {
            return null;
        }

        $sale_type_id = $this->resolveDefaultSaleTypeId($business_id);

        [$price_map, $stock_map] = $this->resolvePricingAndStock(collect([$product]), $business_id, null, $sale_type_id);

        $wishlist_flags = $this->resolveWishlistFlags($business_id, $user_id, [$product->product_id]);

        // Resolved once per request (not per-variation) so the "coin badge"
        // eligibility flag never re-queries CustomerSetting per option.
        $loyalty_context = $this->loyaltyEligibilityContext($business_id);

        $variations = $product->productVariations;

        $options = $variations->map(function ($variation) use ($price_map, $stock_map, $wishlist_flags, $loyalty_context) {
            $entry = $price_map[$variation->product_variation_id] ?? ['price' => 0.0, 'oldPrice' => null, 'discount' => 0];

            return [
                'id' => $variation->product_variation_id,
                'label' => $variation->name,
                'sku' => $variation->sku,
                'price' => $entry['price'],
                'oldPrice' => $entry['oldPrice'],
                'discount' => $entry['discount'],
                'stock' => $stock_map[$variation->product_variation_id] ?? null,
                'is_wishlisted' => !empty($wishlist_flags['variation_ids'][$variation->product_variation_id]),
                'loyaltyEligible' => $this->resolveLoyaltyEligible($loyalty_context, $variation->is_loyalty_enabled),
                'attributes' => $variation->attributes->map(function ($attr) {
                    return ['name' => $attr->name, 'value' => $attr->value];
                })->values()->all(),
            ];
        })->values();

        $primary_option = $options->first();

        $related_products = $this->websiteBaseQuery($business_id)
            ->where('product_id', '!=', $product->product_id)
            ->where('category_id', $product->category_id)
            ->with($this->websiteWith())
            ->limit(8)
            ->get();

        [$related_price_map, $related_stock_map] = $this->resolvePricingAndStock($related_products, $business_id, null, $sale_type_id);

        $related_flags = $this->resolveWishlistFlags(
            $business_id,
            $user_id,
            $related_products->pluck('product_id')->all()
        );

        $related_mapped = $related_products->map(function ($p) use ($related_price_map, $related_stock_map, $related_flags, $loyalty_context) {
            return $this->mapProductSummary($p, $related_price_map, $related_stock_map, $related_flags, $loyalty_context);
        })->values()->all();

        $is_product_wishlisted = !empty($wishlist_flags['product_ids'][$product->product_id]);
        $wishlisted_variation_ids = array_keys(array_filter($wishlist_flags['variation_ids'] ?? []));

        return [
            'id' => $product->product_id,
            'slug' => $product->slug,
            'name' => $product->name,
            'sku' => $primary_option['sku'] ?? null,
            'short_description' => $product->short_description,
            'description' => $product->description,
            // Same flat shape as mapProductSummary() (listing/sections/related
            // products) - one consistent product contract everywhere, not a
            // nested shape only the detail endpoint uses.
            'category' => $product->category->name ?? null,
            'category_id' => $product->category_id,
            'subcategory' => $product->subCategory->name ?? null,
            'sub_category_id' => $product->sub_category_id,
            'brand' => $product->brand->name ?? null,
            'brand_id' => $product->brand_id,
            'images' => $product->productImages->pluck('image_url')->values()->all(),
            'features' => $product->productFeatures->map(function ($feature) {
                return ['name' => $feature->name, 'description' => $feature->description];
            })->values()->all(),
            'is_single_variation' => $variations->count() <= 1,
            'is_wishlisted' => $is_product_wishlisted,
            'is_product_wishlisted' => $is_product_wishlisted,
            'has_wishlisted_variation' => !empty($wishlisted_variation_ids),
            'wishlisted_variation_ids' => $wishlisted_variation_ids,
            'variations' => [
                'label' => 'Options',
                'options' => $options->all(),
            ],
            'price' => $primary_option['price'] ?? 0.0,
            'oldPrice' => $primary_option['oldPrice'] ?? null,
            'discount' => $primary_option['discount'] ?? 0,
            'stock' => $primary_option['stock'] ?? null,
            'default_variation_id' => $primary_option['id'] ?? null,
            'loyaltyEligible' => $primary_option['loyaltyEligible'] ?? $this->resolveLoyaltyEligible($loyalty_context, $product->is_loyalty_enabled),
            'related_products' => $related_mapped,
        ];
    }

    /**
     * Base query shared by every storefront read: active, not-deleted,
     * website-visible products for the given business.
     */
    private function websiteBaseQuery(string $business_id)
    {
        return Product::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->where('is_website_visible', 1);
    }

    /**
     * Eager-load shape shared by every storefront read, avoiding N+1 across
     * category/subCategory/brand/images/variations/attributes/prices.
     */
    private function websiteWith(): array
    {
        return [
            'category:category_id,name',
            'subCategory:sub_category_id,name',
            'brand:brand_id,name',
            'productImages' => function ($q) {
                $q->orderByDesc('is_default')->orderBy('sorting');
            },
            'productVariations.prices',
            'productVariations.attributes',
        ];
    }

    /**
     * The business's default Sale Type (is_default=1, else lowest sort_order
     * among active/non-deleted) - resolved once per request and reused for
     * every price resolution so the whole response is priced consistently.
     */
    private function resolveDefaultSaleTypeId(string $business_id): ?string
    {
        $sale_type = SaleType::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->first();

        return $sale_type->sale_type_id ?? null;
    }

    /**
     * Resolves price and stock for every variation across the given products
     * collection in two bulk queries (not one per variation), keyed by
     * product_variation_id. Single source of truth for storefront
     * price/stock resolution - reused by listing, sections, detail, and
     * related products so there is exactly one implementation of each.
     *
     * @return array{0: array, 1: array} [price_map, stock_map]
     */
    private function resolvePricingAndStock(Collection $products, string $business_id, ?string $branch_id, ?string $sale_type_id): array
    {
        $variation_ids = $products->flatMap(function ($product) {
            return $product->productVariations->pluck('product_variation_id');
        })->filter()->unique()->values()->all();

        $price_map = $this->resolveVariationPriceMap($variation_ids, $sale_type_id);
        $stock_sums = $this->resolveVariationStockSums($variation_ids, $business_id, $branch_id);

        $stock_map = [];
        foreach ($products as $product) {
            $is_tracked = (bool) $product->is_track_stock;
            foreach ($product->productVariations as $variation) {
                $stock_map[$variation->product_variation_id] = $is_tracked
                    ? (float) ($stock_sums[$variation->product_variation_id] ?? 0)
                    : null; // untracked = unlimited, mirrors OrderService::attachAvailableStock()
            }
        }

        return [$price_map, $stock_map];
    }

    /**
     * Resolves the net (post-discount) price, pre-discount price, and
     * discount percentage for a set of variation ids, reusing
     * VariationPricingService - the same math OrderService uses to price a
     * POS/order line - so the storefront never disagrees with checkout.
     */
    private function resolveVariationPriceMap(array $variation_ids, ?string $sale_type_id): array
    {
        if (empty($variation_ids)) {
            return [];
        }

        $resolved = $this->pricing_service->resolveBulk($variation_ids, $sale_type_id);

        $price_map = [];
        foreach ($variation_ids as $variation_id) {
            $r = $resolved[$variation_id] ?? null;
            $base = $r ? (float) $r['price'] : 0.0;
            $discount = $r ? (float) $r['discount_percentage'] : 0.0;

            $price_map[$variation_id] = [
                'price' => $discount > 0 ? round($base * (1 - $discount / 100), 2) : round($base, 2),
                'oldPrice' => $discount > 0 ? round($base, 2) : null,
                'discount' => $discount,
            ];
        }

        return $price_map;
    }

    /**
     * Sums ProductVariationStock.quantity per variation across the relevant
     * warehouses - a single branch's warehouse(s) when $branch_id is given,
     * else every warehouse belonging to the business. Mirrors
     * OrderService::attachAvailableStock()'s pattern, generalized to
     * multiple warehouses.
     */
    private function resolveVariationStockSums(array $variation_ids, string $business_id, ?string $branch_id)
    {
        if (empty($variation_ids)) {
            return collect();
        }

        $warehouse_query = Warehouse::where('business_id', $business_id)->where('is_deleted', 0);

        if (!empty($branch_id)) {
            $warehouse_query->where('branch_id', $branch_id);
        }

        $warehouse_ids = $warehouse_query->pluck('warehouse_id');

        if ($warehouse_ids->isEmpty()) {
            return collect();
        }

        return ProductVariationStock::where('business_id', $business_id)
            ->whereIn('warehouse_id', $warehouse_ids)
            ->whereIn('product_variation_id', $variation_ids)
            ->selectRaw('product_variation_id, SUM(quantity) as qty')
            ->groupBy('product_variation_id')
            ->pluck('qty', 'product_variation_id');
    }

    /**
     * Maps a Product (with its eager-loaded relations) into the flat summary
     * shape used across listing/sections/related-products. The "primary"
     * variation representing sku/price/oldPrice/discount/stock is whichever
     * variation resolves to the lowest net price (the single-variation case
     * degenerates to just that variation).
     */
    /**
     * Resolves the storefront "coin badge" eligibility context once per
     * request - whether the Loyalty Program is on for this business, and
     * whether it earns per-product (vs. per-order). Callers reuse this
     * across every product/variation in the response instead of each row
     * re-querying CustomerSetting via LoyaltyPointService::productEligible().
     * Mirrors, and must stay identical to, the rule in
     * LoyaltyPointService::productEligible().
     */
    private function loyaltyEligibilityContext(string $business_id): array
    {
        $setting = app(LoyaltyPointService::class)->getSetting($business_id);
        $enabled = (bool) ($setting->loyalty_program ?? false);

        return [
            'enabled' => $enabled,
            'mode_product' => $enabled && $setting && $setting->loyalty_earning_mode === 'product',
        ];
    }

    /**
     * Applies the LoyaltyPointService::productEligible() rule using an
     * already-resolved context (see loyaltyEligibilityContext()) plus the
     * product/variation's own is_loyalty_enabled flag.
     */
    private function resolveLoyaltyEligible(array $loyalty_context, $is_loyalty_enabled): bool
    {
        if (!$loyalty_context['enabled']) {
            return false;
        }

        if (!$loyalty_context['mode_product']) {
            return true;
        }

        return (bool) $is_loyalty_enabled;
    }

    private function mapProductSummary(Product $product, array $price_map, array $stock_map, array $wishlist_flags = [], array $loyalty_context = ['enabled' => false, 'mode_product' => false]): array
    {
        $variations = $product->productVariations;

        $primary = null;
        $primary_price = null;

        foreach ($variations as $variation) {
            $entry = $price_map[$variation->product_variation_id] ?? null;
            if ($entry === null) {
                continue;
            }
            if ($primary === null || $entry['price'] < $primary_price) {
                $primary = $variation;
                $primary_price = $entry['price'];
            }
        }

        if ($primary === null) {
            $primary = $variations->first();
        }

        $price_entry = $primary ? ($price_map[$primary->product_variation_id] ?? null) : null;
        $stock_value = $primary ? ($stock_map[$primary->product_variation_id] ?? null) : null;

        $badges = [];
        if ($product->is_featured) {
            $badges[] = 'Featured';
        }
        if ($product->is_trending) {
            $badges[] = 'Trending';
        }
        if ($product->is_best_seller) {
            $badges[] = 'Best Seller';
        }
        if (!empty($product->date_created) && Carbon::parse($product->date_created)->greaterThan(now()->subDays(30))) {
            $badges[] = 'New';
        }

        $is_product_wishlisted = !empty($wishlist_flags['product_ids'][$product->product_id]);
        $has_wishlisted_variation = false;
        foreach ($variations as $variation) {
            if (!empty($wishlist_flags['variation_ids'][$variation->product_variation_id])) {
                $has_wishlisted_variation = true;
                break;
            }
        }

        return [
            'id' => $product->product_id,
            'sku' => $primary->sku ?? null,
            'slug' => $product->slug,
            'name' => $product->name,
            'category' => $product->category->name ?? null,
            'category_id' => $product->category_id,
            'subcategory' => $product->subCategory->name ?? null,
            'sub_category_id' => $product->sub_category_id,
            'brand' => $product->brand->name ?? null,
            'brand_id' => $product->brand_id,
            'price' => $price_entry['price'] ?? 0.0,
            'oldPrice' => $price_entry['oldPrice'] ?? null,
            'discount' => $price_entry['discount'] ?? 0,
            'stock' => $stock_value,
            'default_variation_id' => $primary->product_variation_id ?? null,
            'loyaltyEligible' => $this->resolveLoyaltyEligible($loyalty_context, $product->is_loyalty_enabled),
            'is_single_variation' => $variations->count() <= 1,
            'badges' => $badges,
            'images' => $product->productImages->pluck('image_url')->values()->all(),
            'short_description' => $product->short_description,
            'is_wishlisted' => $is_product_wishlisted || $has_wishlisted_variation,
            'is_product_wishlisted' => $is_product_wishlisted,
            'has_wishlisted_variation' => $has_wishlisted_variation,
        ];
    }

    /**
     * Optional wishlist enrichment for authenticated storefront requests.
     */
    private function resolveWishlistFlags(string $business_id, $user_id, array $product_ids = []): array
    {
        if (!$user_id) {
            return ['product_ids' => [], 'variation_ids' => []];
        }

        return app(WishlistService::class)->flagsForUser((int) $user_id, $business_id, $product_ids);
    }

    /**
     * Builds the 5 homepage sections (each capped at 12) - only computed by
     * getWebsiteListing() when the request has no filters and is on page 1.
     *
     * Featured / Trending / New Arrivals / Best Sellers prioritize matching
     * products, then fill remaining slots with other website-visible products
     * (no duplicate product_ids within a section) so rails stay populated when
     * the catalog has inventory. Discounted Products never falls back — only
     * products with a valid current variation discount are included; an empty
     * array means the storefront should hide that section entirely.
     */
    private function buildWebsiteSections(string $business_id, ?string $branch_id, ?string $sale_type_id, $user_id = null): array
    {
        $with = $this->websiteWith();
        $limit = 12;

        $featured = $this->fillWebsiteSection(
            $this->websiteBaseQuery($business_id)->with($with)->where('is_featured', 1)->orderByDesc('date_created')->limit($limit)->get(),
            $business_id,
            $with,
            $limit
        );

        // No filler products — only variations with an actual discount that
        // applies to all sale types or to the request's sale type.
        $discounted = $this->websiteBaseQuery($business_id)->with($with)->whereHas('productVariations', function ($q) use ($sale_type_id) {
            $q->where('discount_percentage', '>', 0)
                ->where(function ($inner) use ($sale_type_id) {
                    $inner->where('discount_apply_all', 1);
                    if (!empty($sale_type_id)) {
                        $inner->orWhereHas('discountSaleTypes', function ($st) use ($sale_type_id) {
                            $st->where('sale_types.sale_type_id', $sale_type_id);
                        });
                    }
                });
        })->orderByDesc('date_created')->limit($limit * 2)->get();

        $trending = $this->fillWebsiteSection(
            $this->websiteBaseQuery($business_id)->with($with)->where('is_trending', 1)->orderByDesc('date_created')->limit($limit)->get(),
            $business_id,
            $with,
            $limit
        );

        $new_arrivals = $this->fillWebsiteSection(
            $this->websiteBaseQuery($business_id)->with($with)->orderByDesc('date_created')->limit($limit)->get(),
            $business_id,
            $with,
            $limit
        );

        $best_sellers = $this->fillWebsiteSection(
            $this->websiteBaseQuery($business_id)->with($with)->where('is_best_seller', 1)->orderByDesc('date_created')->limit($limit)->get(),
            $business_id,
            $with,
            $limit
        );

        $all = $featured->concat($discounted)->concat($trending)->concat($new_arrivals)->concat($best_sellers);

        [$price_map, $stock_map] = $this->resolvePricingAndStock($all, $business_id, $branch_id, $sale_type_id);

        // Drop candidates whose resolved (sale-type-aware) discount is 0 so
        // the Discounted rail never lists products that are not actually on sale.
        $discounted = $discounted->filter(function ($product) use ($price_map) {
            foreach ($product->productVariations as $variation) {
                $entry = $price_map[$variation->product_variation_id] ?? null;
                if ($entry && ($entry['discount'] ?? 0) > 0) {
                    return true;
                }
            }
            return false;
        })->take($limit)->values();

        $wishlist_flags = $this->resolveWishlistFlags(
            $business_id,
            $user_id,
            $all->pluck('product_id')->unique()->values()->all()
        );

        // Resolved once per request (not per-product) so the "coin badge"
        // eligibility flag never re-queries CustomerSetting per row.
        $loyalty_context = $this->loyaltyEligibilityContext($business_id);

        $map = function (Collection $collection) use ($price_map, $stock_map, $wishlist_flags, $loyalty_context) {
            return $collection->map(function ($product) use ($price_map, $stock_map, $wishlist_flags, $loyalty_context) {
                return $this->mapProductSummary($product, $price_map, $stock_map, $wishlist_flags, $loyalty_context);
            })->values()->all();
        };

        return [
            'featured_products' => $map($featured),
            'discounted_products' => $map($discounted),
            'trending_products' => $map($trending),
            'new_arrivals' => $map($new_arrivals),
            'best_sellers' => $map($best_sellers),
        ];
    }

    /**
     * Keeps prioritized section products first, then fills remaining slots up
     * to $limit with other website-visible products (newest first), excluding
     * product_ids already in the primary set so a section never duplicates.
     */
    private function fillWebsiteSection(Collection $primary, string $business_id, array $with, int $limit): Collection
    {
        $primary = $primary->take($limit)->values();

        if ($primary->count() >= $limit) {
            return $primary;
        }

        $exclude_ids = $primary->pluck('product_id')->filter()->values()->all();
        $needed = $limit - $primary->count();

        $fillers = $this->websiteBaseQuery($business_id)
            ->with($with)
            ->when(!empty($exclude_ids), function ($q) use ($exclude_ids) {
                $q->whereNotIn('product_id', $exclude_ids);
            })
            ->orderByDesc('date_created')
            ->limit($needed)
            ->get();

        return $primary->concat($fillers)->values();
    }

    /**
     * Sorts already-mapped [product, summary] rows in PHP (sort options need
     * fields - is_featured, date_created - that only exist on the model,
     * not the flat summary).
     */
    private function sortWebsiteRows(Collection $rows, string $sort): Collection
    {
        switch ($sort) {
            case 'price_asc':
                return $rows->sortBy(fn ($row) => $row['summary']['price'])->values();
            case 'price_desc':
                return $rows->sortByDesc(fn ($row) => $row['summary']['price'])->values();
            case 'name_asc':
                return $rows->sortBy(fn ($row) => strtolower($row['summary']['name'] ?? ''))->values();
            case 'name_desc':
                return $rows->sortByDesc(fn ($row) => strtolower($row['summary']['name'] ?? ''))->values();
            case 'newest':
                return $rows->sortByDesc(fn ($row) => (string) $row['product']->date_created)->values();
            case 'featured':
            default:
                return $rows->sort(function ($a, $b) {
                    $fa = $a['product']->is_featured ? 1 : 0;
                    $fb = $b['product']->is_featured ? 1 : 0;
                    if ($fa !== $fb) {
                        return $fb <=> $fa;
                    }
                    return strcmp((string) $b['product']->date_created, (string) $a['product']->date_created);
                })->values();
        }
    }
}
