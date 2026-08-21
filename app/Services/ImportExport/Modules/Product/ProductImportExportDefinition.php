<?php

namespace App\Services\ImportExport\Modules\Product;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductVariationAttribute;
use App\Models\ProductVariationPrice;
use App\Models\ProductVariationPriceHistory;
use App\Models\SaleType;
use App\Models\SubCategory;
use App\Models\Unit;
use App\Services\Concrete\Admin\BarcodeService;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ChildTableDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'product';
    }

    public function label(): string
    {
        return 'Products';
    }

    public function modelClass(): string
    {
        return Product::class;
    }

    public function primaryKey(): string
    {
        return 'product_id';
    }

    public function isBranchScoped(): bool
    {
        return false;
    }

    public function groupKeyColumn(): ?string
    {
        return 'Product Name';
    }

    public function childRelationName(): ?string
    {
        return 'productVariations';
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Product Name',
                attribute: 'name',
                type: 'string',
                required: true,
                sampleValues: ['Coca Cola 500ml', 'Coca Cola 500ml'],
                exportAccessor: 'name',
                notes: 'Repeat this exact value on every variation line belonging to the same product.',
            ),
            new ColumnDefinition(
                key: 'Category',
                attribute: 'category_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(Category::class, 'category', 'name', scopeToBusiness: true),
                sampleValues: ['Beverages', 'Beverages'],
                exportAccessor: fn ($m) => $m->category->name ?? '',
            ),
            new ColumnDefinition(
                key: 'Sub Category',
                attribute: 'sub_category_id',
                type: 'relation',
                required: false,
                relation: new RelationSpec(SubCategory::class, 'sub-category', 'name', scopeToBusiness: true, relatedLabel: 'Sub Category'),
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->subCategory->name ?? '',
            ),
            new ColumnDefinition(
                key: 'Brand',
                attribute: 'brand_id',
                type: 'relation',
                required: false,
                relation: new RelationSpec(Brand::class, 'brand', 'name', scopeToBusiness: true),
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->brand->name ?? '',
            ),
            new ColumnDefinition(
                key: 'Status',
                attribute: 'status',
                type: 'enum',
                required: false,
                enumValues: ['active', 'inactive'],
                sampleValues: ['active', 'active'],
                exportAccessor: 'status',
                notes: 'Defaults to "active" if left blank.',
            ),
        ];
    }

    /**
     * The business's currently-configured active Sale Types - drives the
     * dynamic per-sale-type price columns below, so the import/export sheet
     * always matches whatever Sale Types are presently active in POS
     * Settings (see SaleTypeService).
     */
    protected function activeSaleTypes()
    {
        return SaleType::where('business_id', Auth::user()->business_id ?? null)
            ->where('status', 'active')
            ->where('is_deleted', 0)
            ->orderBy('sort_order')
            ->get();
    }

    protected function priceColumnAttribute(string $saleTypeId): string
    {
        return 'price_sale_type__' . $saleTypeId;
    }

    public function childDefinition(): ?ChildTableDefinition
    {
        $priceColumns = $this->activeSaleTypes()->map(function ($sale_type) {
            return new ColumnDefinition(
                key: $sale_type->name . ' Price',
                attribute: $this->priceColumnAttribute($sale_type->sale_type_id),
                type: 'decimal',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: function ($variation) use ($sale_type) {
                    $row = $variation->prices->firstWhere('sale_type_id', $sale_type->sale_type_id);

                    return $row ? $row->price : '';
                },
                notes: 'Optional - falls back to Sale Price when left blank.',
            );
        })->all();

        return new ChildTableDefinition(
            modelClass: ProductVariation::class,
            primaryKey: 'product_variation_id',
            foreignKeyAttribute: 'product_id',
            columns: array_merge([
                new ColumnDefinition(
                    key: 'Variation Name',
                    attribute: 'name',
                    type: 'string',
                    required: true,
                    sampleValues: ['500ml Bottle', '1L Bottle'],
                ),
                new ColumnDefinition(
                    key: 'SKU',
                    attribute: 'sku',
                    type: 'string',
                    required: true,
                    sampleValues: ['CC500-001', 'CC1000-001'],
                    notes: 'The variation\'s own SKU. Matches an existing variation on this product (to update it) or creates a new one.',
                ),
                new ColumnDefinition(
                    key: 'Barcode',
                    attribute: 'barcode',
                    type: 'string',
                    required: false,
                    sampleValues: ['', ''],
                    notes: 'Leave blank to auto-generate per the business\'s Barcode & QR settings.',
                ),
                new ColumnDefinition(
                    key: 'Purchase Price',
                    attribute: 'purchase_price',
                    type: 'decimal',
                    required: true,
                    sampleValues: ['50', '90'],
                ),
                new ColumnDefinition(
                    key: 'Sale Price',
                    attribute: 'sale_price',
                    type: 'decimal',
                    required: true,
                    sampleValues: ['70', '120'],
                ),
                new ColumnDefinition(
                    key: 'Base Unit',
                    attribute: 'base_unit_id',
                    type: 'relation',
                    required: true,
                    relation: new RelationSpec(Unit::class, 'unit', 'name', scopeToBusiness: false),
                    sampleValues: ['Piece', 'Piece'],
                ),
                new ColumnDefinition(
                    key: 'Purchase Unit',
                    attribute: 'purchase_unit_id',
                    type: 'relation',
                    required: false,
                    relation: new RelationSpec(Unit::class, 'unit', 'name', scopeToBusiness: false),
                    sampleValues: ['', ''],
                ),
                new ColumnDefinition(
                    key: 'Sale Unit',
                    attribute: 'sale_unit_id',
                    type: 'relation',
                    required: false,
                    relation: new RelationSpec(Unit::class, 'unit', 'name', scopeToBusiness: false),
                    sampleValues: ['', ''],
                ),
                new ColumnDefinition(
                    key: 'Minimum Stock',
                    attribute: 'minimum_stock',
                    type: 'decimal',
                    required: false,
                    sampleValues: ['', ''],
                ),
                new ColumnDefinition(
                    key: 'Minimum Selling Price',
                    attribute: 'minimum_selling_price',
                    type: 'decimal',
                    required: false,
                    sampleValues: ['', ''],
                    notes: 'Optional hard price floor for this variation - the final price after any discount cannot fall below it without the order.price.override-minimum permission.',
                ),
                new ColumnDefinition(
                    key: 'Discount Percentage',
                    attribute: 'discount_percentage',
                    type: 'decimal',
                    required: false,
                    sampleValues: ['', ''],
                    notes: 'Auto-applied at POS add-to-cart time, per the sale types selected in "Apply Discount On".',
                ),
                new ColumnDefinition(
                    key: 'Discount Apply To',
                    attribute: 'discount_apply_to',
                    type: 'string',
                    required: false,
                    sampleValues: ['All', ''],
                    exportAccessor: function ($variation) {
                        if ($variation->discount_apply_all) {
                            return 'All';
                        }

                        return $variation->discountSaleTypes->pluck('code')->implode(',');
                    },
                    notes: 'Comma-separated Sale Type codes this variation\'s discount applies to (e.g. RETAIL,WHOLESALE), or "All" / blank for every active Sale Type.',
                ),
            ], $priceColumns, [
                new ColumnDefinition(
                    key: 'Attributes',
                    attribute: 'attributes',
                    type: 'string',
                    required: false,
                    sampleValues: ['Color:Red;Size:M', ''],
                    notes: 'Encode as Key:Value pairs separated by semicolons, e.g. Color:Red;Size:M.',
                ),
            ]),
            minChildren: 1,
            deleteExistingOnUpdate: false,
        );
    }

    public function uniqueKeyColumns(): array
    {
        return ['name'];
    }

    /**
     * Full custom save() - mirrors ProductService::save(), but variations are
     * matched by SKU (create-or-update) instead of the generic child
     * delete-then-reinsert convention, since re-importing a product's
     * variations should update the same variation rows (preserving their IDs
     * and any stock ties) rather than recreating them every time. Unlike the
     * manual "edit product" screen, variations already on the product but not
     * present in this import file are left untouched (not soft-deleted) -
     * a partial import file should never be able to silently wipe variations.
     */
    public function save(ResolvedRow $row, ImportContext $ctx): array
    {
        $businessId = $ctx->businessId;

        if ($row->action === 'update') {
            $product = Product::findOrFail($row->matchedId);
            $product->update([
                'category_id' => $row->attributes['category_id'],
                'sub_category_id' => $row->attributes['sub_category_id'] ?? null,
                'brand_id' => $row->attributes['brand_id'] ?? null,
                'status' => $row->attributes['status'] ?? $product->status,
                'updatedby_id' => $ctx->userId,
                'date_updated' => now(),
            ]);
            $created = false;
        } else {
            $limit = checkPackageLimit('products');
            if (!$limit['status']) {
                throw new \Exception($limit['message']);
            }

            $product = Product::create([
                'product_id' => generateUuid(),
                'business_id' => $businessId,
                'name' => $row->attributes['name'],
                'slug' => $this->generateUniqueSlug($row->attributes['name'], $businessId),
                'category_id' => $row->attributes['category_id'],
                'sub_category_id' => $row->attributes['sub_category_id'] ?? null,
                'brand_id' => $row->attributes['brand_id'] ?? null,
                'status' => $row->attributes['status'] ?? 'active',
                'createdby_id' => $ctx->userId,
                'date_created' => now(),
            ]);
            $created = true;
        }

        $barcodeService = app(BarcodeService::class);

        foreach ($row->children as $child) {
            if ($child->action === 'invalid') {
                continue;
            }

            $sku = $child->attributes['sku'];

            $variation = ProductVariation::where('product_id', $product->product_id)
                ->where('is_deleted', 0)
                ->whereRaw('LOWER(sku) = ?', [mb_strtolower($sku)])
                ->first();

            $discountApplyToRaw = trim((string) ($child->attributes['discount_apply_to'] ?? ''));
            $discountApplyAll = $discountApplyToRaw === '' || strtolower($discountApplyToRaw) === 'all';

            $variationData = [
                'name' => $child->attributes['name'],
                'sku' => $sku,
                'base_unit_id' => $child->attributes['base_unit_id'],
                'purchase_unit_id' => $child->attributes['purchase_unit_id'] ?? null,
                'sale_unit_id' => $child->attributes['sale_unit_id'] ?? null,
                'purchase_price' => $child->attributes['purchase_price'],
                'sale_price' => $child->attributes['sale_price'],
                'minimum_stock' => $child->attributes['minimum_stock'] ?? 0,
                'minimum_selling_price' => $child->attributes['minimum_selling_price'] ?? null,
                'discount_percentage' => $child->attributes['discount_percentage'] ?? 0,
                'discount_apply_all' => $discountApplyAll,
                'business_id' => $businessId,
            ];

            if ($variation) {
                $variation->update(array_merge($variationData, [
                    'updatedby_id' => $ctx->userId,
                    'date_updated' => now(),
                ]));
            } else {
                $variation = ProductVariation::create(array_merge($variationData, [
                    'product_variation_id' => generateUuid(),
                    'product_id' => $product->product_id,
                    'createdby_id' => $ctx->userId,
                    'date_created' => now(),
                ]));
            }

            $barcodeService->generateForVariation($variation, $child->attributes['barcode'] ?? null);

            $this->savePricingFromImport($variation, $businessId, $child->attributes, $discountApplyAll, $discountApplyToRaw, $ctx);

            ProductVariationAttribute::where('product_variation_id', $variation->product_variation_id)->delete();

            foreach ($this->parseAttributes($child->attributes['attributes'] ?? null) as $name => $value) {
                ProductVariationAttribute::create([
                    'product_variation_attribute_id' => generateUuid(),
                    'product_variation_id' => $variation->product_variation_id,
                    'name' => $name,
                    'value' => $value,
                    'createdby_id' => $ctx->userId,
                    'date_created' => now(),
                ]);
            }
        }

        return ['model' => $product, 'created' => $created];
    }

    /**
     * Mirrors ProductService::savePricingForVariation() - writes the
     * per-sale-type price columns (see childDefinition()'s dynamic
     * $priceColumns) and the "Discount Apply To" column into
     * product_variation_prices / product_variation_discount_sale_types,
     * logging a ProductVariationPriceHistory row for every sale type whose
     * price actually changed.
     */
    protected function savePricingFromImport(ProductVariation $variation, ?string $businessId, array $attributes, bool $discountApplyAll, string $discountApplyToRaw, ImportContext $ctx): void
    {
        $saleTypes = $this->activeSaleTypes();

        $existingPrices = ProductVariationPrice::where('product_variation_id', $variation->product_variation_id)
            ->pluck('price', 'sale_type_id');

        ProductVariationPrice::where('product_variation_id', $variation->product_variation_id)->delete();

        foreach ($saleTypes as $sale_type) {
            $value = $attributes[$this->priceColumnAttribute($sale_type->sale_type_id)] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $price = (float) $value;

            ProductVariationPrice::create([
                'product_variation_price_id' => generateUuid(),
                'business_id' => $businessId,
                'product_variation_id' => $variation->product_variation_id,
                'sale_type_id' => $sale_type->sale_type_id,
                'price' => $price,
                'createdby_id' => $ctx->userId,
                'date_created' => now(),
            ]);

            $oldPrice = $existingPrices->has($sale_type->sale_type_id) ? (float) $existingPrices->get($sale_type->sale_type_id) : null;

            if ($oldPrice !== null && abs($oldPrice - $price) < 0.0001) {
                continue;
            }

            ProductVariationPriceHistory::create([
                'product_variation_price_history_id' => generateUuid(),
                'business_id' => $businessId,
                'product_variation_id' => $variation->product_variation_id,
                'sale_type_id' => $sale_type->sale_type_id,
                'old_price' => $oldPrice,
                'new_price' => $price,
                'changedby_id' => $ctx->userId,
                'date_created' => now(),
            ]);
        }

        DB::table('product_variation_discount_sale_types')->where('product_variation_id', $variation->product_variation_id)->delete();

        if (!$discountApplyAll) {
            $codes = array_filter(array_map('trim', explode(',', $discountApplyToRaw)));
            $matched = $saleTypes->whereIn('code', $codes);

            foreach ($matched as $sale_type) {
                DB::table('product_variation_discount_sale_types')->insert([
                    'product_variation_id' => $variation->product_variation_id,
                    'sale_type_id' => $sale_type->sale_type_id,
                ]);
            }
        }
    }

    /**
     * Replicates the create screen's client-side slug generation
     * (lowercase, non-alphanumeric runs collapsed to "-") server-side for
     * import, since there is no browser to generate it - then de-dupes
     * against the same business_id + is_deleted=0 scope the manual
     * Rule::unique() validation uses.
     */
    protected function generateUniqueSlug(string $name, ?string $businessId): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while (
            Product::where('business_id', $businessId)
                ->where('is_deleted', 0)
                ->whereRaw('LOWER(slug) = ?', [mb_strtolower($slug)])
                ->exists()
        ) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * "Color:Red;Size:M" -> ['Color' => 'Red', 'Size' => 'M'].
     */
    protected function parseAttributes(?string $raw): array
    {
        $attributes = [];

        if (!$raw || trim($raw) === '') {
            return $attributes;
        }

        foreach (explode(';', $raw) as $pair) {
            $pair = trim($pair);
            if ($pair === '' || !str_contains($pair, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $pair, 2);
            $name = trim($name);
            $value = trim($value);

            if ($name !== '') {
                $attributes[$name] = $value;
            }
        }

        return $attributes;
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = Product::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('name');
    }

    public function exportEagerLoads(): array
    {
        return [
            'category',
            'subCategory',
            'brand',
            'productVariations.unit',
            'productVariations.purchaseUnit',
            'productVariations.saleUnit',
            'productVariations.attributes',
            'productVariations.prices',
            'productVariations.discountSaleTypes',
        ];
    }
}
