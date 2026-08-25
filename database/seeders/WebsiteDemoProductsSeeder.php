<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\ProductVariationPrice;
use App\Models\ProductVariationStock;
use App\Models\SaleType;
use App\Models\SubCategory;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Populates the storefront demo catalog for one business: a handful of
 * extra categories/subcategories/brands for variety (the business only had
 * 3 categories / 1 brand), then 25+ realistic products spanning single and
 * variable types, discounted/non-discounted, in-stock/low-stock/out-of-
 * stock/untracked, with generated placeholder images so image_url always
 * resolves to a real file (no external network dependency).
 *
 * Run: php artisan db:seed --class=WebsiteDemoProductsSeeder
 */
class WebsiteDemoProductsSeeder extends Seeder
{
    private string $businessId = 'c902e8a3-d5dc-4f9b-9018-c1fab4805a75';
    private string $warehouseId = '1a500c31-7706-49d4-b722-19b8d827135b';

    /** @var array<string,string> unit key => unit_id */
    private array $units = [];

    /** @var array<string,array{category_id:string,sub:array<string,string>}> */
    private array $categories = [];

    /** @var array<string,string> brand key => brand_id */
    private array $brands = [];

    public function run(): void
    {
        $this->loadUnits();
        $this->seedCategories();
        $this->seedBrands();
        $this->seedProducts();
    }

    private function loadUnits(): void
    {
        $this->units = [
            'kg' => Unit::where('name', 'KG')->value('unit_id'),
            'ltr' => Unit::where('name', 'Ltr')->value('unit_id'),
            'pc' => Unit::where('name', 'Piece')->value('unit_id'),
        ];
    }

    private function seedCategories(): void
    {
        // Reuse the 2 existing categories that already have real subcategories.
        $beverages = Category::where('business_id', $this->businessId)->where('name', 'Beverages')->first();
        $snacks = Category::where('business_id', $this->businessId)->where('name', 'Snacks')->first();

        $this->categories['beverages'] = [
            'category_id' => $beverages->category_id,
            'sub' => ['carbonated' => \App\Models\SubCategory::where('category_id', $beverages->category_id)->where('name', 'Carbonated Drinks')->value('sub_category_id')],
        ];
        $this->categories['snacks'] = [
            'category_id' => $snacks->category_id,
            'sub' => ['chips' => \App\Models\SubCategory::where('category_id', $snacks->category_id)->where('name', 'Chips')->value('sub_category_id')],
        ];

        // New categories, needed for real product variety.
        $definitions = [
            'dairy' => ['Dairy & Eggs', ['milk' => 'Milk & Cream', 'eggs' => 'Eggs']],
            'produce' => ['Fruits & Vegetables', ['fruits' => 'Fresh Fruits', 'veg' => 'Fresh Vegetables']],
            'bakery' => ['Bakery & Bread', ['bread' => 'Bread', 'pastries' => 'Pastries']],
            'frozen' => ['Frozen Foods', ['meals' => 'Frozen Meals', 'icecream' => 'Ice Cream']],
            'household' => ['Household & Cleaning', ['laundry' => 'Laundry', 'cleaning' => 'Cleaning Supplies']],
            'personal_care' => ['Personal Care', ['skin' => 'Skin Care', 'hair' => 'Hair Care']],
        ];

        foreach ($definitions as $key => [$name, $subs]) {
            $categoryId = (string) Str::uuid();
            Category::create([
                'category_id' => $categoryId,
                'business_id' => $this->businessId,
                'name' => $name,
                'status' => 'active',
                'is_deleted' => 0,
                'date_created' => now(),
            ]);

            $subIds = [];
            foreach ($subs as $subKey => $subName) {
                $subId = (string) Str::uuid();
                SubCategory::create([
                    'sub_category_id' => $subId,
                    'category_id' => $categoryId,
                    'business_id' => $this->businessId,
                    'name' => $subName,
                    'status' => 'active',
                    'is_deleted' => 0,
                    'date_created' => now(),
                ]);
                $subIds[$subKey] = $subId;
            }

            $this->categories[$key] = ['category_id' => $categoryId, 'sub' => $subIds];
        }
    }

    private function seedBrands(): void
    {
        $this->brands['local'] = Brand::where('business_id', $this->businessId)->where('name', 'Local')->value('brand_id');

        foreach (['FarmFresh', 'DairyPure', 'GoldenHarvest', 'GreenLeaf', 'HomeStyle'] as $name) {
            $id = (string) Str::uuid();
            Brand::create([
                'brand_id' => $id,
                'business_id' => $this->businessId,
                'name' => $name,
                'status' => 'active',
                'is_deleted' => 0,
                'date_created' => now(),
            ]);
            $this->brands[strtolower($name)] = $id;
        }
    }

    private function seedProducts(): void
    {
        $wholesaleSaleTypeId = SaleType::where('business_id', $this->businessId)->where('name', 'Wholesale')->value('sale_type_id');

        $catalog = $this->catalog();

        foreach ($catalog as $index => $def) {
            $slug = $this->uniqueSlug($def['name']);
            $productId = (string) Str::uuid();

            $isVariable = count($def['variations']) > 1;

            Product::create([
                'product_id' => $productId,
                'business_id' => $this->businessId,
                'category_id' => $this->categories[$def['category']]['category_id'],
                'sub_category_id' => $this->categories[$def['category']]['sub'][$def['sub']],
                'brand_id' => $this->brands[$def['brand']],
                'name' => $def['name'],
                'slug' => $slug,
                'type' => $isVariable ? 'variable' : 'single',
                'usage_type' => 'saleable',
                'is_track_stock' => $def['stock'] === 'unlimited' ? 0 : 1,
                'is_pos_visible' => 1,
                'is_website_visible' => 1,
                'is_app_visible' => 1,
                'short_description' => $def['short'],
                'description' => $def['description'],
                'is_featured' => in_array('featured', $def['flags'], true) ? 1 : 0,
                'is_trending' => in_array('trending', $def['flags'], true) ? 1 : 0,
                'is_best_seller' => in_array('bestseller', $def['flags'], true) ? 1 : 0,
                'status' => 'active',
                'is_deleted' => 0,
                'date_created' => now()->subDays($def['age_days'] ?? 0),
            ]);

            $stockQty = match ($def['stock']) {
                'zero' => 0,
                'low' => rand(2, 8),
                'unlimited' => 0,
                default => rand(30, 120),
            };

            foreach ($def['variations'] as $vi => $variation) {
                $variationId = (string) Str::uuid();
                $sku = strtoupper(Str::slug($def['name'], '')) . '-' . strtoupper(substr($variationId, 0, 6));

                ProductVariation::create([
                    'product_variation_id' => $variationId,
                    'business_id' => $this->businessId,
                    'product_id' => $productId,
                    'sku' => $sku,
                    'name' => $variation['label'],
                    'base_unit_id' => $this->units[$def['unit']],
                    'purchase_unit_id' => $this->units[$def['unit']],
                    'sale_unit_id' => $this->units[$def['unit']],
                    'purchase_price' => round($variation['price'] * 0.6, 2),
                    'sale_price' => $variation['price'],
                    'minimum_selling_price' => round($variation['price'] * 0.7, 2),
                    'discount_percentage' => $def['discount'],
                    'discount_apply_all' => 1,
                    'minimum_stock' => 5,
                    'track_batch' => 0,
                    'track_expiry' => 0,
                    'status' => 'active',
                    'is_deleted' => 0,
                    'date_created' => now(),
                ]);

                if ($def['stock'] !== 'unlimited') {
                    ProductVariationStock::create([
                        'product_variation_stock_id' => (string) Str::uuid(),
                        'business_id' => $this->businessId,
                        'product_id' => $productId,
                        'product_variation_id' => $variationId,
                        'warehouse_id' => $this->warehouseId,
                        'avg_price' => round($variation['price'] * 0.6, 2),
                        'quantity' => $stockQty,
                        'status' => 'active',
                        'is_deleted' => 0,
                        'date_created' => now(),
                    ]);
                }

                if (!empty($def['wholesale']) && $wholesaleSaleTypeId && $vi === 0) {
                    ProductVariationPrice::create([
                        'product_variation_price_id' => (string) Str::uuid(),
                        'business_id' => $this->businessId,
                        'product_variation_id' => $variationId,
                        'sale_type_id' => $wholesaleSaleTypeId,
                        'price' => round($variation['price'] * 0.85, 2),
                        'minimum_selling_price' => round($variation['price'] * 0.6, 2),
                        'date_created' => now(),
                    ]);
                }
            }

            $this->seedImages($productId, $def['name'], $index);
        }
    }

    /**
     * Generates 2 placeholder JPEGs per product with GD (solid colour +
     * large centred product-name text via the system Arial Bold TTF) into
     * the existing public/uploads/product/ folder, so ProductImage's
     * image_url accessor resolves real, legible files with no code changes
     * and no external network dependency.
     */
    private function seedImages(string $productId, string $name, int $index): void
    {
        $palette = [
            [46, 125, 90], [198, 93, 45], [45, 90, 150], [150, 60, 110],
            [90, 130, 45], [180, 140, 30], [70, 70, 150], [160, 60, 60],
        ];

        for ($i = 0; $i < 2; $i++) {
            $color = $palette[($index + $i) % count($palette)];
            $filename = Str::uuid() . '.jpg';
            $path = public_path('uploads/product/' . $filename);

            self::renderPlaceholderImage($path, $name, $color, $i);

            ProductImage::create([
                'product_image_id' => (string) Str::uuid(),
                'product_id' => $productId,
                'image' => $filename,
                'sorting' => $i,
                'is_default' => $i === 0 ? 1 : 0,
                'status' => 'active',
                'date_created' => now(),
            ]);
        }
    }

    public static function renderPlaceholderImage(string $path, string $name, array $color, int $shade = 0): void
    {
        $font = 'C:\\Windows\\Fonts\\arialbd.ttf';
        $size = 800;

        $img = imagecreatetruecolor($size, $size);
        $bg = imagecolorallocate($img, max($color[0] - $shade * 15, 0), max($color[1] - $shade * 15, 0), max($color[2] - $shade * 15, 0));
        imagefill($img, 0, 0, $bg);

        $white = imagecolorallocate($img, 255, 255, 255);
        $shadow = imagecolorallocatealpha($img, 0, 0, 0, 60);

        $fontSize = 42;
        $maxWidth = $size - 120;
        $words = explode(' ', $name);
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $candidate = trim($line . ' ' . $word);
            $box = imagettfbbox($fontSize, 0, $font, $candidate);
            $width = $box[2] - $box[0];
            if ($width > $maxWidth && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        $lineHeight = $fontSize + 18;
        $startY = (int) ($size / 2 - (count($lines) * $lineHeight / 2) + $fontSize);
        foreach ($lines as $li => $text) {
            $box = imagettfbbox($fontSize, 0, $font, $text);
            $textWidth = $box[2] - $box[0];
            $x = (int) (($size - $textWidth) / 2);
            $y = $startY + $li * $lineHeight;
            imagettftext($img, $fontSize, 0, $x + 2, $y + 2, $shadow, $font, $text);
            imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $text);
        }

        imagejpeg($img, $path, 88);
        imagedestroy($img);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $n = 1;
        while (Product::where('business_id', $this->businessId)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$n);
        }
        return $slug;
    }

    /**
     * @return array<int, array{
     *   name:string, category:string, sub:string, brand:string, unit:string,
     *   short:string, description:string, flags:string[], discount:float,
     *   stock:string, variations:array<int,array{label:string,price:float}>,
     *   wholesale?:bool, age_days?:int
     * }>
     */
    private function catalog(): array
    {
        return [
            ['name' => 'Sparkling Orange Soda', 'category' => 'beverages', 'sub' => 'carbonated', 'brand' => 'goldenharvest', 'unit' => 'ltr',
                'short' => 'Refreshing carbonated orange soda.', 'description' => 'A crisp, fizzy orange soda made with real fruit flavour, perfect chilled on a hot day.',
                'flags' => ['featured'], 'discount' => 10, 'stock' => 'normal',
                'variations' => [['label' => '500ml', 'price' => 1.99], ['label' => '1L', 'price' => 3.49], ['label' => '1.5L', 'price' => 4.49]]],

            ['name' => 'Natural Spring Water', 'category' => 'beverages', 'sub' => 'carbonated', 'brand' => 'farmfresh', 'unit' => 'ltr',
                'short' => 'Pure natural spring water.', 'description' => 'Sourced from protected springs and bottled at source for a clean, crisp taste.',
                'flags' => ['bestseller'], 'discount' => 0, 'stock' => 'unlimited',
                'variations' => [['label' => '1.5L', 'price' => 1.29]]],

            ['name' => 'Cold Brew Coffee', 'category' => 'beverages', 'sub' => 'carbonated', 'brand' => 'homestyle', 'unit' => 'ltr',
                'short' => 'Smooth slow-steeped cold brew.', 'description' => 'Steeped for 18 hours for a naturally sweet, low-acid coffee you can drink straight from the fridge.',
                'flags' => ['trending'], 'discount' => 15, 'stock' => 'low',
                'variations' => [['label' => '250ml', 'price' => 3.99]]],

            ['name' => 'Fresh Orange Juice', 'category' => 'beverages', 'sub' => 'carbonated', 'brand' => 'farmfresh', 'unit' => 'ltr',
                'short' => '100% pure squeezed orange juice.', 'description' => 'No added sugar, no concentrate — just fresh oranges pressed the same day.',
                'flags' => [], 'discount' => 0, 'stock' => 'normal', 'age_days' => 3,
                'variations' => [['label' => '1L', 'price' => 4.29]]],

            ['name' => 'Classic Potato Chips', 'category' => 'snacks', 'sub' => 'chips', 'brand' => 'local', 'unit' => 'pc',
                'short' => 'Crispy kettle-cooked potato chips.', 'description' => 'Thick-cut and kettle-cooked for extra crunch, available in three classic flavours.',
                'flags' => ['bestseller'], 'discount' => 0, 'stock' => 'normal', 'wholesale' => true,
                'variations' => [['label' => 'Salted 150g', 'price' => 2.49], ['label' => 'Sour Cream 150g', 'price' => 2.49], ['label' => 'BBQ 150g', 'price' => 2.49]]],

            ['name' => 'Chocolate Chip Cookies', 'category' => 'snacks', 'sub' => 'chips', 'brand' => 'greenleaf', 'unit' => 'pc',
                'short' => 'Soft-baked chocolate chip cookies.', 'description' => 'Loaded with real chocolate chips and baked soft in the centre, crisp at the edges.',
                'flags' => ['featured'], 'discount' => 20, 'stock' => 'normal',
                'variations' => [['label' => '200g', 'price' => 3.29]]],

            ['name' => 'Mixed Nuts', 'category' => 'snacks', 'sub' => 'chips', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'A hearty mix of roasted nuts.', 'description' => 'Almonds, cashews, and pecans, lightly roasted and salted.',
                'flags' => [], 'discount' => 0, 'stock' => 'zero',
                'variations' => [['label' => '300g', 'price' => 5.99]]],

            ['name' => 'Popcorn Sweet & Salty', 'category' => 'snacks', 'sub' => 'chips', 'brand' => 'dairypure', 'unit' => 'pc',
                'short' => 'Perfectly balanced sweet and salty popcorn.', 'description' => 'Air-popped and coated in a light caramel-and-sea-salt glaze.',
                'flags' => ['trending'], 'discount' => 0, 'stock' => 'normal',
                'variations' => [['label' => '100g', 'price' => 2.79]]],

            ['name' => 'Fresh Whole Milk', 'category' => 'dairy', 'sub' => 'milk', 'brand' => 'dairypure', 'unit' => 'ltr',
                'short' => 'Farm-fresh whole milk.', 'description' => 'Pasteurised and bottled within 24 hours of milking for maximum freshness.',
                'flags' => ['bestseller'], 'discount' => 0, 'stock' => 'unlimited',
                'variations' => [['label' => '1L', 'price' => 2.19]]],

            ['name' => 'Greek Yogurt', 'category' => 'dairy', 'sub' => 'milk', 'brand' => 'dairypure', 'unit' => 'pc',
                'short' => 'Thick and creamy strained yogurt.', 'description' => 'Strained three times for an extra-thick, protein-rich texture.',
                'flags' => ['featured'], 'discount' => 10, 'stock' => 'normal',
                'variations' => [['label' => 'Plain 500g', 'price' => 3.99], ['label' => 'Strawberry 500g', 'price' => 4.29], ['label' => 'Vanilla 500g', 'price' => 4.29]]],

            ['name' => 'Cheddar Cheese Block', 'category' => 'dairy', 'sub' => 'eggs', 'brand' => 'farmfresh', 'unit' => 'pc',
                'short' => 'Aged cheddar cheese block.', 'description' => 'Matured for 12 months for a sharp, rounded flavour.',
                'flags' => [], 'discount' => 0, 'stock' => 'low',
                'variations' => [['label' => '400g', 'price' => 6.49]]],

            ['name' => 'Farm Fresh Eggs', 'category' => 'dairy', 'sub' => 'eggs', 'brand' => 'farmfresh', 'unit' => 'pc',
                'short' => 'Free-range eggs, dozen pack.', 'description' => 'Laid by free-range hens and collected daily.',
                'flags' => ['trending'], 'discount' => 0, 'stock' => 'normal',
                'variations' => [['label' => '12 pcs', 'price' => 3.49]]],

            ['name' => 'Bananas', 'category' => 'produce', 'sub' => 'fruits', 'brand' => 'local', 'unit' => 'kg',
                'short' => 'Sweet ripe bananas.', 'description' => 'Naturally ripened and ready to eat, sold by the kilogram.',
                'flags' => ['bestseller'], 'discount' => 0, 'stock' => 'unlimited', 'wholesale' => true,
                'variations' => [['label' => '1kg', 'price' => 1.49]]],

            ['name' => 'Red Apples', 'category' => 'produce', 'sub' => 'fruits', 'brand' => 'greenleaf', 'unit' => 'kg',
                'short' => 'Crisp and juicy red apples.', 'description' => 'Hand-picked for the perfect balance of sweet and tart.',
                'flags' => ['featured'], 'discount' => 12, 'stock' => 'normal',
                'variations' => [['label' => '1kg', 'price' => 2.99]]],

            ['name' => 'Organic Spinach', 'category' => 'produce', 'sub' => 'veg', 'brand' => 'greenleaf', 'unit' => 'kg',
                'short' => 'Tender organic baby spinach.', 'description' => 'Grown without synthetic pesticides, washed and ready to use.',
                'flags' => [], 'discount' => 0, 'stock' => 'zero',
                'variations' => [['label' => '250g', 'price' => 2.29]]],

            ['name' => 'Tomatoes', 'category' => 'produce', 'sub' => 'veg', 'brand' => 'local', 'unit' => 'kg',
                'short' => 'Vine-ripened tomatoes.', 'description' => 'Grown to full ripeness on the vine for maximum flavour.',
                'flags' => ['trending'], 'discount' => 0, 'stock' => 'normal',
                'variations' => [['label' => '1kg', 'price' => 2.49]]],

            ['name' => 'Whole Wheat Bread', 'category' => 'bakery', 'sub' => 'bread', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Freshly baked whole wheat loaf.', 'description' => 'Baked daily with 100% whole wheat flour, soft crumb and hearty crust.',
                'flags' => ['bestseller'], 'discount' => 0, 'stock' => 'low',
                'variations' => [['label' => '500g', 'price' => 2.99]]],

            ['name' => 'Butter Croissants', 'category' => 'bakery', 'sub' => 'pastries', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Flaky all-butter croissants.', 'description' => 'Laminated with real butter for a light, flaky texture, pack of 4.',
                'flags' => ['featured'], 'discount' => 18, 'stock' => 'normal',
                'variations' => [['label' => '4 pcs', 'price' => 4.99]]],

            ['name' => 'Chocolate Muffins', 'category' => 'bakery', 'sub' => 'pastries', 'brand' => 'greenleaf', 'unit' => 'pc',
                'short' => 'Rich double chocolate muffins.', 'description' => 'Moist chocolate muffins studded with chocolate chunks, pack of 6.',
                'flags' => [], 'discount' => 0, 'stock' => 'normal',
                'variations' => [['label' => '6 pcs', 'price' => 5.49]]],

            ['name' => 'Vanilla Ice Cream Tub', 'category' => 'frozen', 'sub' => 'icecream', 'brand' => 'dairypure', 'unit' => 'ltr',
                'short' => 'Creamy classic ice cream.', 'description' => 'Made with real cream and vanilla, churned slowly for a smooth texture.',
                'flags' => ['trending'], 'discount' => 15, 'stock' => 'normal',
                'variations' => [['label' => 'Vanilla 1L', 'price' => 5.99], ['label' => 'Chocolate 1L', 'price' => 5.99], ['label' => 'Strawberry 1L', 'price' => 5.99]]],

            ['name' => 'Frozen Mixed Vegetables', 'category' => 'frozen', 'sub' => 'meals', 'brand' => 'farmfresh', 'unit' => 'pc',
                'short' => 'Flash-frozen vegetable medley.', 'description' => 'Peas, carrots, and corn, flash-frozen at peak freshness.',
                'flags' => [], 'discount' => 0, 'stock' => 'unlimited',
                'variations' => [['label' => '500g', 'price' => 2.79]]],

            ['name' => 'Chicken Nuggets', 'category' => 'frozen', 'sub' => 'meals', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Crispy breaded chicken nuggets.', 'description' => 'Made with real chicken breast, breaded and ready to bake or fry.',
                'flags' => ['bestseller'], 'discount' => 0, 'stock' => 'low',
                'variations' => [['label' => '1kg', 'price' => 7.99]]],

            ['name' => 'Laundry Detergent', 'category' => 'household', 'sub' => 'laundry', 'brand' => 'homestyle', 'unit' => 'ltr',
                'short' => 'Concentrated liquid laundry detergent.', 'description' => 'Tough on stains, gentle on fabric, with a light fresh scent.',
                'flags' => ['featured'], 'discount' => 10, 'stock' => 'normal',
                'variations' => [['label' => 'Original 2L', 'price' => 8.99], ['label' => 'Lavender 2L', 'price' => 8.99]]],

            ['name' => 'All-Purpose Cleaner', 'category' => 'household', 'sub' => 'cleaning', 'brand' => 'greenleaf', 'unit' => 'pc',
                'short' => 'Multi-surface cleaning spray.', 'description' => 'Cuts through grease and grime on kitchen, bathroom, and household surfaces.',
                'flags' => [], 'discount' => 0, 'stock' => 'normal',
                'variations' => [['label' => '750ml', 'price' => 3.49]]],

            ['name' => 'Dish Soap', 'category' => 'household', 'sub' => 'cleaning', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Grease-cutting dish soap.', 'description' => 'A little goes a long way — cuts grease fast and rinses clean.',
                'flags' => ['trending'], 'discount' => 0, 'stock' => 'zero',
                'variations' => [['label' => '500ml', 'price' => 2.99]]],

            ['name' => 'Moisturizing Body Lotion', 'category' => 'personal_care', 'sub' => 'skin', 'brand' => 'greenleaf', 'unit' => 'pc',
                'short' => '24-hour hydrating body lotion.', 'description' => 'Lightweight, fast-absorbing formula that keeps skin hydrated all day.',
                'flags' => ['featured'], 'discount' => 20, 'stock' => 'normal',
                'variations' => [['label' => '400ml', 'price' => 6.99]]],

            ['name' => 'Shampoo & Conditioner', 'category' => 'personal_care', 'sub' => 'hair', 'brand' => 'greenleaf', 'unit' => 'pc',
                'short' => 'Nourishing hair care duo.', 'description' => 'Sulfate-free formula that cleans and conditions without weighing hair down.',
                'flags' => ['bestseller'], 'discount' => 0, 'stock' => 'normal',
                'variations' => [['label' => 'Shampoo 500ml', 'price' => 7.49], ['label' => 'Conditioner 500ml', 'price' => 7.49], ['label' => 'Combo Pack', 'price' => 13.99]]],

            ['name' => 'Herbal Hand Soap', 'category' => 'personal_care', 'sub' => 'skin', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Gentle herbal hand wash.', 'description' => 'Infused with aloe and chamomile for soft, clean hands.',
                'flags' => [], 'discount' => 0, 'stock' => 'unlimited',
                'variations' => [['label' => '250ml', 'price' => 2.49]]],
        ];
    }
}
