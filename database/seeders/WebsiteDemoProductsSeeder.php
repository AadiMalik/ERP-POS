<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\ProductVariationAttribute;
use App\Models\ProductVariationPrice;
use App\Models\ProductVariationStock;
use App\Models\SaleType;
use App\Models\SubCategory;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Seeds a full storefront demo catalog for the active business:
 * categories + subcategories + brands, then 50 products
 * (15 single-variation, 35 multi-variation), stock qty 20 on every
 * variation, website flags (featured/trending/best seller), discounts,
 * and real Unsplash images downloaded into public/uploads/product/.
 *
 * Business/warehouse resolve automatically (override with
 * WEBSITE_DEMO_BUSINESS_ID / WEBSITE_DEMO_WAREHOUSE_ID env vars).
 *
 * Run: php artisan db:seed --class=WebsiteDemoProductsSeeder
 * Idempotent by product slug — re-running skips products that already exist.
 */
class WebsiteDemoProductsSeeder extends Seeder
{
    private string $businessId;
    private string $warehouseId;

    /** @var array<string,string> */
    private array $units = [];

    /** @var array<string,array{category_id:string,sub:array<string,string>}> */
    private array $categories = [];

    /** @var array<string,string> */
    private array $brands = [];

    public function run(): void
    {
        $this->resolveTenant();
        $this->loadUnits();
        $this->seedCategories();
        $this->seedBrands();
        $this->seedProducts();

        $this->command?->info("Demo catalog seeded for business {$this->businessId} (warehouse {$this->warehouseId}).");
    }

    private function resolveTenant(): void
    {
        $this->businessId = env('WEBSITE_DEMO_BUSINESS_ID')
            ?: Business::where('is_deleted', 0)->orderBy('date_created')->value('business_id');

        if (!$this->businessId) {
            throw new \RuntimeException('No business found to seed demo products into.');
        }

        $this->warehouseId = env('WEBSITE_DEMO_WAREHOUSE_ID')
            ?: Warehouse::where('business_id', $this->businessId)->where('is_deleted', 0)->value('warehouse_id');

        if (!$this->warehouseId) {
            throw new \RuntimeException("No warehouse found for business {$this->businessId}.");
        }
    }

    private function loadUnits(): void
    {
        $pieces = Unit::where('is_deleted', 0)->where(function ($q) {
            $q->where('name', 'Pieces')->orWhere('name', 'Piece')->orWhere('name', 'Pc');
        })->value('unit_id');

        $kg = Unit::where('is_deleted', 0)->where(function ($q) {
            $q->where('name', 'KG')->orWhere('name', 'Kg')->orWhere('name', 'Kilogram');
        })->value('unit_id');

        if (!$pieces || !$kg) {
            throw new \RuntimeException('Need units named Pieces/Piece and KG before seeding demo products.');
        }

        $this->units = ['pc' => $pieces, 'kg' => $kg];
    }

    private function seedCategories(): void
    {
        $definitions = [
            'beverages' => ['Beverages', ['soft' => 'Soft Drinks', 'juice' => 'Juices', 'water' => 'Water & Tea']],
            'snacks' => ['Snacks', ['chips' => 'Chips & Crisps', 'sweets' => 'Cookies & Sweets', 'nuts' => 'Nuts & Trail Mix']],
            'dairy' => ['Dairy & Eggs', ['milk' => 'Milk & Cream', 'yogurt' => 'Yogurt', 'eggs' => 'Eggs & Cheese']],
            'produce' => ['Fruits & Vegetables', ['fruits' => 'Fresh Fruits', 'veg' => 'Fresh Vegetables']],
            'bakery' => ['Bakery & Bread', ['bread' => 'Bread', 'pastries' => 'Pastries']],
            'frozen' => ['Frozen Foods', ['meals' => 'Frozen Meals', 'icecream' => 'Ice Cream']],
            'household' => ['Household & Cleaning', ['laundry' => 'Laundry', 'cleaning' => 'Cleaning Supplies']],
            'personal_care' => ['Personal Care', ['skin' => 'Skin Care', 'hair' => 'Hair Care']],
        ];

        foreach ($definitions as $key => [$name, $subs]) {
            $category = Category::firstOrCreate(
                ['business_id' => $this->businessId, 'name' => $name, 'is_deleted' => 0],
                [
                    'category_id' => (string) Str::uuid(),
                    'status' => 'active',
                    'date_created' => now(),
                ]
            );

            $subIds = [];
            foreach ($subs as $subKey => $subName) {
                $sub = SubCategory::firstOrCreate(
                    [
                        'business_id' => $this->businessId,
                        'category_id' => $category->category_id,
                        'name' => $subName,
                        'is_deleted' => 0,
                    ],
                    [
                        'sub_category_id' => (string) Str::uuid(),
                        'status' => 'active',
                        'date_created' => now(),
                    ]
                );
                $subIds[$subKey] = $sub->sub_category_id;
            }

            $this->categories[$key] = ['category_id' => $category->category_id, 'sub' => $subIds];
        }
    }

    private function seedBrands(): void
    {
        foreach (['Local', 'FarmFresh', 'DairyPure', 'GoldenHarvest', 'GreenLeaf', 'HomeStyle', 'PureSip', 'CrunchBox'] as $name) {
            $brand = Brand::firstOrCreate(
                ['business_id' => $this->businessId, 'name' => $name, 'is_deleted' => 0],
                [
                    'brand_id' => (string) Str::uuid(),
                    'status' => 'active',
                    'date_created' => now(),
                ]
            );
            $this->brands[strtolower($name)] = $brand->brand_id;
        }
    }

    private function seedProducts(): void
    {
        $wholesaleSaleTypeId = SaleType::where('business_id', $this->businessId)->where('name', 'Wholesale')->value('sale_type_id');
        $catalog = $this->catalog();

        $created = 0;
        $skipped = 0;

        foreach ($catalog as $index => $def) {
            $slug = $this->uniqueSlug($def['name']);
            if (Product::where('business_id', $this->businessId)->where('slug', Str::slug($def['name']))->where('is_deleted', 0)->exists()
                || Product::where('business_id', $this->businessId)->where('name', $def['name'])->where('is_deleted', 0)->exists()) {
                $skipped++;
                continue;
            }

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
                'is_track_stock' => 1,
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

                if (!empty($variation['attrs']) && is_array($variation['attrs'])) {
                    foreach ($variation['attrs'] as $attrName => $attrValue) {
                        ProductVariationAttribute::create([
                            'product_variation_attribute_id' => (string) Str::uuid(),
                            'product_variation_id' => $variationId,
                            'name' => $attrName,
                            'value' => $attrValue,
                            'date_created' => now(),
                        ]);
                    }
                }

                ProductVariationStock::create([
                    'product_variation_stock_id' => (string) Str::uuid(),
                    'business_id' => $this->businessId,
                    'product_id' => $productId,
                    'product_variation_id' => $variationId,
                    'warehouse_id' => $this->warehouseId,
                    'avg_price' => round($variation['price'] * 0.6, 2),
                    'quantity' => 20,
                    'status' => 'active',
                    'is_deleted' => 0,
                    'date_created' => now(),
                ]);

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

            $this->seedImages($productId, $def['image'] ?? null, $index);
            $created++;
        }

        $this->command?->info("Products created: {$created}, skipped (already exist): {$skipped}.");
    }

    /**
     * Downloads a real product photo into public/uploads/product/ and stores
     * the local filename on product_images (image_url accessor prefixes that path).
     */
    private function seedImages(string $productId, ?string $unsplashPhotoId, int $index): void
    {
        $dir = public_path('uploads/product');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $photoIds = $this->unsplashPhotoIds();
        $photoId = $unsplashPhotoId ?: $photoIds[$index % count($photoIds)];

        $sources = [
            "https://images.unsplash.com/{$photoId}?auto=format&fit=crop&w=800&h=800&q=80",
            "https://picsum.photos/seed/demo-{$index}/800/800.jpg",
        ];

        $filename = null;
        foreach ($sources as $url) {
            try {
                $response = Http::timeout(20)->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; DukanazDemoSeeder/1.0)',
                ])->get($url);

                if ($response->successful() && strlen($response->body()) > 1000) {
                    $filename = (string) Str::uuid() . '.jpg';
                    file_put_contents($dir . DIRECTORY_SEPARATOR . $filename, $response->body());
                    break;
                }
            } catch (\Throwable $e) {
                // try next source
            }
        }

        if (!$filename) {
            $filename = (string) Str::uuid() . '.jpg';
            $this->renderFallbackImage($dir . DIRECTORY_SEPARATOR . $filename, $index);
        }

        ProductImage::create([
            'product_image_id' => (string) Str::uuid(),
            'product_id' => $productId,
            'image' => $filename,
            'sorting' => 0,
            'is_default' => 1,
            'status' => 'active',
            'date_created' => now(),
        ]);

        // Second image from a different photo for gallery variety.
        $secondPhoto = $photoIds[($index + 7) % count($photoIds)];
        $secondFilename = null;
        try {
            $response = Http::timeout(20)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; DukanazDemoSeeder/1.0)',
            ])->get("https://images.unsplash.com/{$secondPhoto}?auto=format&fit=crop&w=800&h=800&q=80");
            if ($response->successful() && strlen($response->body()) > 1000) {
                $secondFilename = (string) Str::uuid() . '.jpg';
                file_put_contents($dir . DIRECTORY_SEPARATOR . $secondFilename, $response->body());
            }
        } catch (\Throwable $e) {
            // optional second image
        }

        if ($secondFilename) {
            ProductImage::create([
                'product_image_id' => (string) Str::uuid(),
                'product_id' => $productId,
                'image' => $secondFilename,
                'sorting' => 1,
                'is_default' => 0,
                'status' => 'active',
                'date_created' => now(),
            ]);
        }
    }

    private function renderFallbackImage(string $path, int $index): void
    {
        $palette = [
            [46, 125, 90], [198, 93, 45], [45, 90, 150], [150, 60, 110],
            [90, 130, 45], [180, 140, 30], [70, 70, 150], [160, 60, 60],
        ];
        $color = $palette[$index % count($palette)];
        $img = imagecreatetruecolor(800, 800);
        $bg = imagecolorallocate($img, $color[0], $color[1], $color[2]);
        imagefill($img, 0, 0, $bg);
        imagejpeg($img, $path, 88);
        imagedestroy($img);
    }

    /** Curated Unsplash photo path segments (photo-…-hash). */
    private function unsplashPhotoIds(): array
    {
        return [
            'photo-1550583724-b2692b85b150', // milk
            'photo-1600271886742-f049cd451bba', // orange juice
            'photo-1625772299848-391b6a87d7b3', // soda
            'photo-1542838132-92c53300491e', // groceries
            'photo-1610832958506-aa56368176cf', // fruits
            'photo-1560806887-1e4cd0b6cbd6', // apples
            'photo-1571771894821-ce9b6c11b08e', // bananas
            'photo-1599599810769-bcde5a160d32', // chips
            'photo-1558961363-fa8fdf82db35', // cookies
            'photo-1486297678162-eb2a19b0a32d', // bread
            'photo-1509440159596-0249088772ff', // bakery
            'photo-1563805042-7684c019e1cb', // ice cream
            'photo-1588168333986-5078d3ae3976', // cheese
            'photo-1582721478779-0b2f2f4c0e6b', // eggs-ish
            'photo-1628088062851-5ec19b9c8d5c', // yogurt
            'photo-1615484477778-da3e5c5c6b5a', // vegetables
            'photo-1590779033100-9f60a05a013d', // produce
            'photo-1583947215259-38e31be8751f', // cleaning
            'photo-1610557892470-55d9e80c0bce', // laundry
            'photo-1556228578-0d85b1a4d571', // lotion
            'photo-1535585209827-a15fcdbc4c2d', // shampoo
            'photo-1607613009820-a29f7bb81c04', // hand soap
            'photo-1551024506-0bccd828d307', // dessert
            'photo-1567620905732-2d1ef2e35ca', // muffins-ish / food
            'photo-1512621776951-a57141f2eefd', // salad / veg
            'photo-1490474418585-ba9bad8fd0ea', // breakfast
            'photo-1478144592103-25e218a04893', // food bowl
            'photo-1467003909585-2f8a72700288', // plated food
            'photo-1504674900247-0877df9cc836', // meal
            'photo-1414235077428-338989a2e8c0', // restaurant dish
            'photo-1498837167922-ddd27525d352', // healthy food
            'photo-1482049016688-2d3e1b311543', // avocado toast
            'photo-1511690656952-34342bb7c2f2', // smoothie bowl
            'photo-1526318896980-cf78c088247c', // coffee
            'photo-1495474472287-4d71bcdd2085', // coffee cups
            'photo-1514432324607-a09d9b4aefdd', // latte
            'photo-1571877227200-a0d98ea607e9', // chocolate
            'photo-1606313564200-e75d5e30476c', // chocolate bar
            'photo-1590080875613-7a7f0a0e9d4b', // nuts
            'photo-1599599810694-b5ac4c0e0c0a', // popcorn
            'photo-1626200419199-391ae4be7a41', // water bottle
            'photo-1560026300-7632fad5c4d7', // tomatoes
            'photo-1597362920021-049f2a0d1a0a', // spinach
            'photo-1615485290382-441e4d049cb5', // frozen veg
            'photo-1562967914-608f82629710', // chicken nuggets vibe
            'photo-1625944230946-1d8e9e1b5f2a', // dish soap
            'photo-1608571423902-eed4a5ad8108', // honey / jar
            'photo-1576045057995-568f588f82fb', // spices
            'photo-1547592166-23ac45744acd', // soup
            'photo-1600271886742-f049cd451bba', // juice glass
        ];
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
     * 50 products: first 15 are single-variation; remaining 35 are multi-variation.
     *
     * @return array<int, array<string, mixed>>
     */
    private function catalog(): array
    {
        $single = [
            ['name' => 'Natural Spring Water 1.5L', 'category' => 'beverages', 'sub' => 'water', 'brand' => 'puresip', 'unit' => 'pc',
                'short' => 'Pure natural spring water.', 'description' => 'Sourced from protected springs and bottled at source for a clean, crisp taste.',
                'flags' => ['bestseller'], 'discount' => 0, 'age_days' => 2,
                'image' => 'photo-1626200419199-391ae4be7a41',
                'variations' => [['label' => '1.5L', 'price' => 1.29]]],

            ['name' => 'Cold Brew Coffee Bottle', 'category' => 'beverages', 'sub' => 'water', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Smooth slow-steeped cold brew.', 'description' => 'Steeped for 18 hours for a naturally sweet, low-acid coffee.',
                'flags' => ['trending'], 'discount' => 15, 'age_days' => 5,
                'image' => 'photo-1514432324607-a09d9b4aefdd',
                'variations' => [['label' => '250ml', 'price' => 3.99]]],

            ['name' => 'Fresh Orange Juice 1L', 'category' => 'beverages', 'sub' => 'juice', 'brand' => 'farmfresh', 'unit' => 'pc',
                'short' => '100% pure squeezed orange juice.', 'description' => 'No added sugar — just fresh oranges pressed the same day.',
                'flags' => ['featured'], 'discount' => 10, 'age_days' => 1,
                'image' => 'photo-1600271886742-f049cd451bba',
                'variations' => [['label' => '1L', 'price' => 4.29]]],

            ['name' => 'Chocolate Chip Cookies Pack', 'category' => 'snacks', 'sub' => 'sweets', 'brand' => 'greenleaf', 'unit' => 'pc',
                'short' => 'Soft-baked chocolate chip cookies.', 'description' => 'Loaded with real chocolate chips, soft in the centre.',
                'flags' => ['featured'], 'discount' => 20, 'age_days' => 8,
                'image' => 'photo-1558961363-fa8fdf82db35',
                'variations' => [['label' => '200g', 'price' => 3.29]]],

            ['name' => 'Mixed Nuts Jar', 'category' => 'snacks', 'sub' => 'nuts', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Roasted almonds, cashews and pecans.', 'description' => 'Lightly roasted and salted for a hearty snack.',
                'flags' => [], 'discount' => 0, 'age_days' => 20,
                'image' => 'photo-1599599810769-bcde5a160d32',
                'variations' => [['label' => '300g', 'price' => 5.99]]],

            ['name' => 'Fresh Whole Milk 1L', 'category' => 'dairy', 'sub' => 'milk', 'brand' => 'dairypure', 'unit' => 'pc',
                'short' => 'Farm-fresh whole milk.', 'description' => 'Pasteurised and bottled within 24 hours of milking.',
                'flags' => ['bestseller'], 'discount' => 0, 'age_days' => 1,
                'image' => 'photo-1550583724-b2692b85b150',
                'variations' => [['label' => '1L', 'price' => 2.19]]],

            ['name' => 'Farm Fresh Eggs Dozen', 'category' => 'dairy', 'sub' => 'eggs', 'brand' => 'farmfresh', 'unit' => 'pc',
                'short' => 'Free-range eggs, dozen pack.', 'description' => 'Laid by free-range hens and collected daily.',
                'flags' => ['trending'], 'discount' => 0, 'age_days' => 3,
                'image' => 'photo-1582721478779-0b2f2f4c0e6b',
                'variations' => [['label' => '12 pcs', 'price' => 3.49]]],

            ['name' => 'Cheddar Cheese Block 400g', 'category' => 'dairy', 'sub' => 'eggs', 'brand' => 'farmfresh', 'unit' => 'pc',
                'short' => 'Aged cheddar cheese block.', 'description' => 'Matured for 12 months for a sharp, rounded flavour.',
                'flags' => [], 'discount' => 12, 'age_days' => 14,
                'image' => 'photo-1588168333986-5078d3ae3976',
                'variations' => [['label' => '400g', 'price' => 6.49]]],

            ['name' => 'Organic Spinach Bag', 'category' => 'produce', 'sub' => 'veg', 'brand' => 'greenleaf', 'unit' => 'kg',
                'short' => 'Tender organic baby spinach.', 'description' => 'Grown without synthetic pesticides, washed and ready.',
                'flags' => ['featured'], 'discount' => 0, 'age_days' => 2,
                'image' => 'photo-1576045057995-568f588f82fb',
                'variations' => [['label' => '250g', 'price' => 2.29]]],

            ['name' => 'Vine Tomatoes 1kg', 'category' => 'produce', 'sub' => 'veg', 'brand' => 'local', 'unit' => 'kg',
                'short' => 'Vine-ripened tomatoes.', 'description' => 'Grown to full ripeness on the vine for maximum flavour.',
                'flags' => ['trending'], 'discount' => 0, 'age_days' => 4,
                'image' => 'photo-1560026300-7632fad5c4d7',
                'variations' => [['label' => '1kg', 'price' => 2.49]]],

            ['name' => 'Whole Wheat Bread Loaf', 'category' => 'bakery', 'sub' => 'bread', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Freshly baked whole wheat loaf.', 'description' => 'Baked daily with 100% whole wheat flour.',
                'flags' => ['bestseller'], 'discount' => 0, 'age_days' => 1,
                'image' => 'photo-1486297678162-eb2a19b0a32d',
                'variations' => [['label' => '500g', 'price' => 2.99]]],

            ['name' => 'Butter Croissants Pack', 'category' => 'bakery', 'sub' => 'pastries', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Flaky all-butter croissants.', 'description' => 'Laminated with real butter, pack of 4.',
                'flags' => ['featured'], 'discount' => 18, 'age_days' => 2,
                'image' => 'photo-1509440159596-0249088772ff',
                'variations' => [['label' => '4 pcs', 'price' => 4.99]]],

            ['name' => 'All-Purpose Cleaner Spray', 'category' => 'household', 'sub' => 'cleaning', 'brand' => 'greenleaf', 'unit' => 'pc',
                'short' => 'Multi-surface cleaning spray.', 'description' => 'Cuts through grease on kitchen and bathroom surfaces.',
                'flags' => [], 'discount' => 0, 'age_days' => 30,
                'image' => 'photo-1583947215259-38e31be8751f',
                'variations' => [['label' => '750ml', 'price' => 3.49]]],

            ['name' => 'Moisturizing Body Lotion', 'category' => 'personal_care', 'sub' => 'skin', 'brand' => 'greenleaf', 'unit' => 'pc',
                'short' => '24-hour hydrating body lotion.', 'description' => 'Lightweight, fast-absorbing formula for all-day hydration.',
                'flags' => ['featured'], 'discount' => 20, 'age_days' => 12,
                'image' => 'photo-1556228578-0d85b1a4d571',
                'variations' => [['label' => '400ml', 'price' => 6.99]]],

            ['name' => 'Herbal Hand Soap', 'category' => 'personal_care', 'sub' => 'skin', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Gentle herbal hand wash.', 'description' => 'Infused with aloe and chamomile for soft, clean hands.',
                'flags' => ['trending'], 'discount' => 0, 'age_days' => 18,
                'image' => 'photo-1607613009820-a29f7bb81c04',
                'variations' => [['label' => '250ml', 'price' => 2.49]]],
        ];

        $multi = [
            ['name' => 'Sparkling Orange Soda', 'category' => 'beverages', 'sub' => 'soft', 'brand' => 'goldenharvest', 'unit' => 'pc',
                'short' => 'Refreshing carbonated orange soda.', 'description' => 'Crisp fizzy orange soda with real fruit flavour.',
                'flags' => ['featured'], 'discount' => 10, 'age_days' => 6, 'wholesale' => true,
                'image' => 'photo-1625772299848-391b6a87d7b3',
                'variations' => [
                    ['label' => '500ml', 'price' => 1.99, 'attrs' => ['Size' => '500ml']],
                    ['label' => '1L', 'price' => 3.49, 'attrs' => ['Size' => '1L']],
                    ['label' => '1.5L', 'price' => 4.49, 'attrs' => ['Size' => '1.5L']],
                ]],

            ['name' => 'Classic Cola', 'category' => 'beverages', 'sub' => 'soft', 'brand' => 'puresip', 'unit' => 'pc',
                'short' => 'Classic cola soft drink.', 'description' => 'The everyday cola — bold flavour, ice-cold best.',
                'flags' => ['bestseller', 'trending'], 'discount' => 5, 'age_days' => 9,
                'image' => 'photo-1625772299848-391b6a87d7b3',
                'variations' => [
                    ['label' => '330ml Can', 'price' => 1.49, 'attrs' => ['Size' => '330ml', 'Pack' => 'Can']],
                    ['label' => '500ml Bottle', 'price' => 1.99, 'attrs' => ['Size' => '500ml', 'Pack' => 'Bottle']],
                    ['label' => '1.5L Bottle', 'price' => 3.99, 'attrs' => ['Size' => '1.5L', 'Pack' => 'Bottle']],
                ]],

            ['name' => 'Lemon Iced Tea', 'category' => 'beverages', 'sub' => 'water', 'brand' => 'puresip', 'unit' => 'pc',
                'short' => 'Light lemon iced tea.', 'description' => 'Brewed tea with a bright lemon finish, lightly sweetened.',
                'flags' => ['trending'], 'discount' => 0, 'age_days' => 7,
                'image' => 'photo-1495474472287-4d71bcdd2085',
                'variations' => [
                    ['label' => '500ml', 'price' => 2.29, 'attrs' => ['Size' => '500ml']],
                    ['label' => '1L', 'price' => 3.79, 'attrs' => ['Size' => '1L']],
                ]],

            ['name' => 'Apple Juice', 'category' => 'beverages', 'sub' => 'juice', 'brand' => 'farmfresh', 'unit' => 'pc',
                'short' => 'Pressed apple juice.', 'description' => 'Made from ripe apples, no concentrate.',
                'flags' => [], 'discount' => 8, 'age_days' => 11,
                'image' => 'photo-1600271886742-f049cd451bba',
                'variations' => [
                    ['label' => '500ml', 'price' => 2.49, 'attrs' => ['Size' => '500ml']],
                    ['label' => '1L', 'price' => 3.99, 'attrs' => ['Size' => '1L']],
                    ['label' => '2L', 'price' => 6.49, 'attrs' => ['Size' => '2L']],
                ]],

            ['name' => 'Classic Potato Chips', 'category' => 'snacks', 'sub' => 'chips', 'brand' => 'crunchbox', 'unit' => 'pc',
                'short' => 'Crispy kettle-cooked potato chips.', 'description' => 'Thick-cut and kettle-cooked for extra crunch.',
                'flags' => ['bestseller'], 'discount' => 0, 'age_days' => 10, 'wholesale' => true,
                'image' => 'photo-1599599810769-bcde5a160d32',
                'variations' => [
                    ['label' => 'Salted 150g', 'price' => 2.49, 'attrs' => ['Flavor' => 'Salted', 'Size' => '150g']],
                    ['label' => 'Sour Cream 150g', 'price' => 2.49, 'attrs' => ['Flavor' => 'Sour Cream', 'Size' => '150g']],
                    ['label' => 'BBQ 150g', 'price' => 2.49, 'attrs' => ['Flavor' => 'BBQ', 'Size' => '150g']],
                ]],

            ['name' => 'Tortilla Chips', 'category' => 'snacks', 'sub' => 'chips', 'brand' => 'crunchbox', 'unit' => 'pc',
                'short' => 'Crunchy corn tortilla chips.', 'description' => 'Perfect with salsa or guacamole.',
                'flags' => ['featured'], 'discount' => 15, 'age_days' => 13,
                'image' => 'photo-1542838132-92c53300491e',
                'variations' => [
                    ['label' => 'Original 200g', 'price' => 2.99, 'attrs' => ['Flavor' => 'Original']],
                    ['label' => 'Cheese 200g', 'price' => 2.99, 'attrs' => ['Flavor' => 'Cheese']],
                    ['label' => 'Spicy 200g', 'price' => 3.19, 'attrs' => ['Flavor' => 'Spicy']],
                ]],

            ['name' => 'Popcorn Sweet & Salty', 'category' => 'snacks', 'sub' => 'sweets', 'brand' => 'crunchbox', 'unit' => 'pc',
                'short' => 'Sweet and salty popcorn.', 'description' => 'Air-popped with a caramel-and-sea-salt glaze.',
                'flags' => ['trending'], 'discount' => 0, 'age_days' => 16,
                'image' => 'photo-1578849278619-e73505e9610f',
                'variations' => [
                    ['label' => '100g', 'price' => 2.79, 'attrs' => ['Size' => '100g']],
                    ['label' => '200g', 'price' => 4.49, 'attrs' => ['Size' => '200g']],
                ]],

            ['name' => 'Trail Mix Energy Pack', 'category' => 'snacks', 'sub' => 'nuts', 'brand' => 'greenleaf', 'unit' => 'pc',
                'short' => 'Nuts, seeds and dried fruit.', 'description' => 'A balanced snack for work or the gym.',
                'flags' => [], 'discount' => 10, 'age_days' => 22,
                'image' => 'photo-1599599810769-bcde5a160d32',
                'variations' => [
                    ['label' => 'Classic 150g', 'price' => 3.99, 'attrs' => ['Blend' => 'Classic']],
                    ['label' => 'Berry 150g', 'price' => 4.29, 'attrs' => ['Blend' => 'Berry']],
                    ['label' => 'Chocolate 150g', 'price' => 4.49, 'attrs' => ['Blend' => 'Chocolate']],
                ]],

            ['name' => 'Greek Yogurt Cups', 'category' => 'dairy', 'sub' => 'yogurt', 'brand' => 'dairypure', 'unit' => 'pc',
                'short' => 'Thick strained Greek yogurt.', 'description' => 'Strained three times for a protein-rich texture.',
                'flags' => ['featured', 'bestseller'], 'discount' => 10, 'age_days' => 4,
                'image' => 'photo-1488477181946-6428a0291777',
                'variations' => [
                    ['label' => 'Plain 500g', 'price' => 3.99, 'attrs' => ['Flavor' => 'Plain']],
                    ['label' => 'Strawberry 500g', 'price' => 4.29, 'attrs' => ['Flavor' => 'Strawberry']],
                    ['label' => 'Vanilla 500g', 'price' => 4.29, 'attrs' => ['Flavor' => 'Vanilla']],
                ]],

            ['name' => 'Flavored Milk Cartons', 'category' => 'dairy', 'sub' => 'milk', 'brand' => 'dairypure', 'unit' => 'pc',
                'short' => 'Ready-to-drink flavored milk.', 'description' => 'Kids’ favourite — chocolate, strawberry, and banana.',
                'flags' => ['trending'], 'discount' => 0, 'age_days' => 5,
                'image' => 'photo-1550583724-b2692b85b150',
                'variations' => [
                    ['label' => 'Chocolate 200ml', 'price' => 1.49, 'attrs' => ['Flavor' => 'Chocolate']],
                    ['label' => 'Strawberry 200ml', 'price' => 1.49, 'attrs' => ['Flavor' => 'Strawberry']],
                    ['label' => 'Banana 200ml', 'price' => 1.49, 'attrs' => ['Flavor' => 'Banana']],
                ]],

            ['name' => 'Sliced Cheese Packs', 'category' => 'dairy', 'sub' => 'eggs', 'brand' => 'dairypure', 'unit' => 'pc',
                'short' => 'Sandwich-ready cheese slices.', 'description' => 'Convenient packs for lunchboxes and burgers.',
                'flags' => [], 'discount' => 5, 'age_days' => 15,
                'image' => 'photo-1588168333986-5078d3ae3976',
                'variations' => [
                    ['label' => 'Cheddar 200g', 'price' => 4.49, 'attrs' => ['Type' => 'Cheddar']],
                    ['label' => 'Mozzarella 200g', 'price' => 4.79, 'attrs' => ['Type' => 'Mozzarella']],
                ]],

            ['name' => 'Red Apples', 'category' => 'produce', 'sub' => 'fruits', 'brand' => 'greenleaf', 'unit' => 'kg',
                'short' => 'Crisp juicy red apples.', 'description' => 'Hand-picked for the perfect sweet-tart balance.',
                'flags' => ['featured', 'bestseller'], 'discount' => 12, 'age_days' => 3, 'wholesale' => true,
                'image' => 'photo-1560806887-1e4cd0b6cbd6',
                'variations' => [
                    ['label' => '1kg', 'price' => 2.99, 'attrs' => ['Pack' => '1kg']],
                    ['label' => '2kg', 'price' => 5.49, 'attrs' => ['Pack' => '2kg']],
                ]],

            ['name' => 'Bananas', 'category' => 'produce', 'sub' => 'fruits', 'brand' => 'local', 'unit' => 'kg',
                'short' => 'Sweet ripe bananas.', 'description' => 'Naturally ripened and ready to eat.',
                'flags' => ['bestseller'], 'discount' => 0, 'age_days' => 2, 'wholesale' => true,
                'image' => 'photo-1571771894821-ce9b6c11b08e',
                'variations' => [
                    ['label' => '1kg', 'price' => 1.49, 'attrs' => ['Pack' => '1kg']],
                    ['label' => '2kg', 'price' => 2.79, 'attrs' => ['Pack' => '2kg']],
                ]],

            ['name' => 'Citrus Mix', 'category' => 'produce', 'sub' => 'fruits', 'brand' => 'farmfresh', 'unit' => 'kg',
                'short' => 'Oranges, lemons and limes.', 'description' => 'Bright citrus selection for juice and cooking.',
                'flags' => ['trending'], 'discount' => 8, 'age_days' => 6,
                'image' => 'photo-1610832958506-aa56368176cf',
                'variations' => [
                    ['label' => 'Oranges 1kg', 'price' => 2.79, 'attrs' => ['Fruit' => 'Oranges']],
                    ['label' => 'Lemons 1kg', 'price' => 2.49, 'attrs' => ['Fruit' => 'Lemons']],
                    ['label' => 'Mixed 1kg', 'price' => 2.99, 'attrs' => ['Fruit' => 'Mixed']],
                ]],

            ['name' => 'Salad Greens Bundle', 'category' => 'produce', 'sub' => 'veg', 'brand' => 'greenleaf', 'unit' => 'kg',
                'short' => 'Ready-to-eat salad greens.', 'description' => 'Washed lettuce mixes for quick salads.',
                'flags' => ['featured'], 'discount' => 0, 'age_days' => 2,
                'image' => 'photo-1512621776951-a57141f2eefd',
                'variations' => [
                    ['label' => 'Iceberg 300g', 'price' => 1.99, 'attrs' => ['Type' => 'Iceberg']],
                    ['label' => 'Romaine 300g', 'price' => 2.29, 'attrs' => ['Type' => 'Romaine']],
                    ['label' => 'Mixed Leaf 300g', 'price' => 2.49, 'attrs' => ['Type' => 'Mixed']],
                ]],

            ['name' => 'Artisan Bread Selection', 'category' => 'bakery', 'sub' => 'bread', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Fresh artisan loaves.', 'description' => 'Baked daily — sourdough, multigrain, and white.',
                'flags' => ['trending'], 'discount' => 0, 'age_days' => 1,
                'image' => 'photo-1509440159596-0249088772ff',
                'variations' => [
                    ['label' => 'Sourdough', 'price' => 3.99, 'attrs' => ['Type' => 'Sourdough']],
                    ['label' => 'Multigrain', 'price' => 3.79, 'attrs' => ['Type' => 'Multigrain']],
                    ['label' => 'White Soft', 'price' => 2.99, 'attrs' => ['Type' => 'White']],
                ]],

            ['name' => 'Chocolate Muffins', 'category' => 'bakery', 'sub' => 'pastries', 'brand' => 'greenleaf', 'unit' => 'pc',
                'short' => 'Double chocolate muffins.', 'description' => 'Moist muffins studded with chocolate chunks.',
                'flags' => ['featured'], 'discount' => 15, 'age_days' => 3,
                'image' => 'photo-1606313564200-e75d5e30476c',
                'variations' => [
                    ['label' => '4 pcs', 'price' => 3.99, 'attrs' => ['Pack' => '4']],
                    ['label' => '6 pcs', 'price' => 5.49, 'attrs' => ['Pack' => '6']],
                    ['label' => '12 pcs', 'price' => 9.99, 'attrs' => ['Pack' => '12']],
                ]],

            ['name' => 'Danish Pastry Box', 'category' => 'bakery', 'sub' => 'pastries', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Assorted breakfast pastries.', 'description' => 'Flaky pastries with fruit and cream fillings.',
                'flags' => [], 'discount' => 10, 'age_days' => 4,
                'image' => 'photo-1509440159596-0249088772ff',
                'variations' => [
                    ['label' => 'Apple 4 pcs', 'price' => 4.49, 'attrs' => ['Flavor' => 'Apple']],
                    ['label' => 'Berry 4 pcs', 'price' => 4.49, 'attrs' => ['Flavor' => 'Berry']],
                    ['label' => 'Assorted 6 pcs', 'price' => 6.49, 'attrs' => ['Flavor' => 'Assorted']],
                ]],

            ['name' => 'Vanilla Ice Cream Tub', 'category' => 'frozen', 'sub' => 'icecream', 'brand' => 'dairypure', 'unit' => 'pc',
                'short' => 'Creamy classic ice cream.', 'description' => 'Made with real cream, churned slowly.',
                'flags' => ['trending', 'bestseller'], 'discount' => 15, 'age_days' => 8,
                'image' => 'photo-1563805042-7684c019e1cb',
                'variations' => [
                    ['label' => 'Vanilla 1L', 'price' => 5.99, 'attrs' => ['Flavor' => 'Vanilla']],
                    ['label' => 'Chocolate 1L', 'price' => 5.99, 'attrs' => ['Flavor' => 'Chocolate']],
                    ['label' => 'Strawberry 1L', 'price' => 5.99, 'attrs' => ['Flavor' => 'Strawberry']],
                ]],

            ['name' => 'Premium Ice Cream Pints', 'category' => 'frozen', 'sub' => 'icecream', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Gourmet ice cream pints.', 'description' => 'Rich scoops with mix-ins — cookie dough and caramel.',
                'flags' => ['featured'], 'discount' => 0, 'age_days' => 12,
                'image' => 'photo-1563805042-7684c019e1cb',
                'variations' => [
                    ['label' => 'Cookie Dough 500ml', 'price' => 4.99, 'attrs' => ['Flavor' => 'Cookie Dough']],
                    ['label' => 'Salted Caramel 500ml', 'price' => 4.99, 'attrs' => ['Flavor' => 'Salted Caramel']],
                ]],

            ['name' => 'Frozen Mixed Vegetables', 'category' => 'frozen', 'sub' => 'meals', 'brand' => 'farmfresh', 'unit' => 'pc',
                'short' => 'Flash-frozen vegetable medley.', 'description' => 'Peas, carrots and corn frozen at peak freshness.',
                'flags' => [], 'discount' => 0, 'age_days' => 25,
                'image' => 'photo-1590779033100-9f60a05a013d',
                'variations' => [
                    ['label' => '500g', 'price' => 2.79, 'attrs' => ['Size' => '500g']],
                    ['label' => '1kg', 'price' => 4.99, 'attrs' => ['Size' => '1kg']],
                ]],

            ['name' => 'Chicken Nuggets', 'category' => 'frozen', 'sub' => 'meals', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Crispy breaded chicken nuggets.', 'description' => 'Made with chicken breast — bake or fry.',
                'flags' => ['bestseller'], 'discount' => 10, 'age_days' => 17,
                'image' => 'photo-1562967914-608f82629710',
                'variations' => [
                    ['label' => '500g', 'price' => 4.99, 'attrs' => ['Size' => '500g']],
                    ['label' => '1kg', 'price' => 7.99, 'attrs' => ['Size' => '1kg']],
                    ['label' => 'Family 1.5kg', 'price' => 10.99, 'attrs' => ['Size' => '1.5kg']],
                ]],

            ['name' => 'Frozen Pizza', 'category' => 'frozen', 'sub' => 'meals', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Ready-to-bake frozen pizza.', 'description' => 'Stone-baked crust with generous toppings.',
                'flags' => ['trending'], 'discount' => 12, 'age_days' => 19,
                'image' => 'photo-1513104890138-7c749659a591',
                'variations' => [
                    ['label' => 'Margherita', 'price' => 5.49, 'attrs' => ['Flavor' => 'Margherita']],
                    ['label' => 'Pepperoni', 'price' => 5.99, 'attrs' => ['Flavor' => 'Pepperoni']],
                    ['label' => 'Veggie', 'price' => 5.79, 'attrs' => ['Flavor' => 'Veggie']],
                ]],

            ['name' => 'Laundry Detergent', 'category' => 'household', 'sub' => 'laundry', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Concentrated liquid laundry detergent.', 'description' => 'Tough on stains, gentle on fabric.',
                'flags' => ['featured'], 'discount' => 10, 'age_days' => 28,
                'image' => 'photo-1610557892470-55d9e80c0bce',
                'variations' => [
                    ['label' => 'Original 2L', 'price' => 8.99, 'attrs' => ['Scent' => 'Original']],
                    ['label' => 'Lavender 2L', 'price' => 8.99, 'attrs' => ['Scent' => 'Lavender']],
                    ['label' => 'Fresh Linen 2L', 'price' => 8.99, 'attrs' => ['Scent' => 'Fresh Linen']],
                ]],

            ['name' => 'Fabric Softener', 'category' => 'household', 'sub' => 'laundry', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Long-lasting softener scent.', 'description' => 'Leaves clothes soft and fresh after every wash.',
                'flags' => [], 'discount' => 0, 'age_days' => 33,
                'image' => 'photo-1610557892470-55d9e80c0bce',
                'variations' => [
                    ['label' => 'Spring 1L', 'price' => 4.49, 'attrs' => ['Scent' => 'Spring']],
                    ['label' => 'Ocean 1L', 'price' => 4.49, 'attrs' => ['Scent' => 'Ocean']],
                ]],

            ['name' => 'Dish Soap', 'category' => 'household', 'sub' => 'cleaning', 'brand' => 'greenleaf', 'unit' => 'pc',
                'short' => 'Grease-cutting dish soap.', 'description' => 'A little goes a long way — cuts grease fast.',
                'flags' => ['trending'], 'discount' => 5, 'age_days' => 21,
                'image' => 'photo-1583947215259-38e31be8751f',
                'variations' => [
                    ['label' => 'Lemon 500ml', 'price' => 2.99, 'attrs' => ['Scent' => 'Lemon']],
                    ['label' => 'Apple 500ml', 'price' => 2.99, 'attrs' => ['Scent' => 'Apple']],
                    ['label' => 'Lemon 1L', 'price' => 4.99, 'attrs' => ['Scent' => 'Lemon', 'Size' => '1L']],
                ]],

            ['name' => 'Paper Towels Mega Pack', 'category' => 'household', 'sub' => 'cleaning', 'brand' => 'local', 'unit' => 'pc',
                'short' => 'Absorbent kitchen paper towels.', 'description' => 'Strong sheets for spills and everyday cleaning.',
                'flags' => ['bestseller'], 'discount' => 0, 'age_days' => 40,
                'image' => 'photo-1583947215259-38e31be8751f',
                'variations' => [
                    ['label' => '6 Rolls', 'price' => 6.99, 'attrs' => ['Pack' => '6']],
                    ['label' => '12 Rolls', 'price' => 11.99, 'attrs' => ['Pack' => '12']],
                ]],

            ['name' => 'Shampoo & Conditioner', 'category' => 'personal_care', 'sub' => 'hair', 'brand' => 'greenleaf', 'unit' => 'pc',
                'short' => 'Nourishing hair care duo.', 'description' => 'Sulfate-free formula that cleans without weighing hair down.',
                'flags' => ['bestseller', 'featured'], 'discount' => 0, 'age_days' => 14,
                'image' => 'photo-1535585209827-a15fcdbc4c2d',
                'variations' => [
                    ['label' => 'Shampoo 500ml', 'price' => 7.49, 'attrs' => ['Type' => 'Shampoo']],
                    ['label' => 'Conditioner 500ml', 'price' => 7.49, 'attrs' => ['Type' => 'Conditioner']],
                    ['label' => 'Combo Pack', 'price' => 13.99, 'attrs' => ['Type' => 'Combo']],
                ]],

            ['name' => 'Face Wash Range', 'category' => 'personal_care', 'sub' => 'skin', 'brand' => 'greenleaf', 'unit' => 'pc',
                'short' => 'Gentle daily face wash.', 'description' => 'Cleanses without stripping — for normal and oily skin.',
                'flags' => ['trending'], 'discount' => 15, 'age_days' => 9,
                'image' => 'photo-1556228578-0d85b1a4d571',
                'variations' => [
                    ['label' => 'Normal Skin 150ml', 'price' => 5.49, 'attrs' => ['Skin Type' => 'Normal']],
                    ['label' => 'Oily Skin 150ml', 'price' => 5.49, 'attrs' => ['Skin Type' => 'Oily']],
                    ['label' => 'Sensitive 150ml', 'price' => 5.99, 'attrs' => ['Skin Type' => 'Sensitive']],
                ]],

            ['name' => 'Toothpaste Family Pack', 'category' => 'personal_care', 'sub' => 'skin', 'brand' => 'homestyle', 'unit' => 'pc',
                'short' => 'Fluoride toothpaste for the family.', 'description' => 'Fresh mint protection for everyday brushing.',
                'flags' => [], 'discount' => 8, 'age_days' => 27,
                'image' => 'photo-1625772299848-391b6a87d7b3',
                'variations' => [
                    ['label' => 'Mint 100g', 'price' => 2.49, 'attrs' => ['Flavor' => 'Mint']],
                    ['label' => 'Whitening 100g', 'price' => 2.99, 'attrs' => ['Flavor' => 'Whitening']],
                    ['label' => 'Kids Berry 80g', 'price' => 2.29, 'attrs' => ['Flavor' => 'Kids Berry']],
                ]],

            ['name' => 'Body Wash Collection', 'category' => 'personal_care', 'sub' => 'skin', 'brand' => 'puresip', 'unit' => 'pc',
                'short' => 'Refreshing shower gels.', 'description' => 'Creamy lather with lasting fragrance.',
                'flags' => ['featured'], 'discount' => 20, 'age_days' => 11,
                'image' => 'photo-1607613009820-a29f7bb81c04',
                'variations' => [
                    ['label' => 'Citrus 400ml', 'price' => 4.99, 'attrs' => ['Scent' => 'Citrus']],
                    ['label' => 'Coconut 400ml', 'price' => 4.99, 'attrs' => ['Scent' => 'Coconut']],
                    ['label' => 'Lavender 400ml', 'price' => 4.99, 'attrs' => ['Scent' => 'Lavender']],
                ]],

            ['name' => 'Energy Drink Cans', 'category' => 'beverages', 'sub' => 'soft', 'brand' => 'goldenharvest', 'unit' => 'pc',
                'short' => 'Sparkling energy drink.', 'description' => 'Caffeine boost with a crisp citrus taste.',
                'flags' => ['trending'], 'discount' => 0, 'age_days' => 7,
                'image' => 'photo-1625772299848-391b6a87d7b3',
                'variations' => [
                    ['label' => 'Original 250ml', 'price' => 2.49, 'attrs' => ['Flavor' => 'Original']],
                    ['label' => 'Zero Sugar 250ml', 'price' => 2.49, 'attrs' => ['Flavor' => 'Zero Sugar']],
                    ['label' => 'Berry 250ml', 'price' => 2.49, 'attrs' => ['Flavor' => 'Berry']],
                ]],

            ['name' => 'Granola Bars Box', 'category' => 'snacks', 'sub' => 'sweets', 'brand' => 'farmfresh', 'unit' => 'pc',
                'short' => 'Wholesome oat granola bars.', 'description' => 'Oats, honey and nuts in a convenient bar.',
                'flags' => ['bestseller'], 'discount' => 10, 'age_days' => 18,
                'image' => 'photo-1558961363-fa8fdf82db35',
                'variations' => [
                    ['label' => 'Honey 6 bars', 'price' => 3.99, 'attrs' => ['Flavor' => 'Honey']],
                    ['label' => 'Chocolate 6 bars', 'price' => 4.29, 'attrs' => ['Flavor' => 'Chocolate']],
                    ['label' => 'Berry 6 bars', 'price' => 4.29, 'attrs' => ['Flavor' => 'Berry']],
                ]],

            ['name' => 'Honey Jar Set', 'category' => 'snacks', 'sub' => 'sweets', 'brand' => 'farmfresh', 'unit' => 'pc',
                'short' => 'Pure natural honey.', 'description' => 'Raw honey from local apiaries.',
                'flags' => ['featured'], 'discount' => 0, 'age_days' => 35,
                'image' => 'photo-1608571423902-eed4a5ad8108',
                'variations' => [
                    ['label' => '250g', 'price' => 4.99, 'attrs' => ['Size' => '250g']],
                    ['label' => '500g', 'price' => 8.49, 'attrs' => ['Size' => '500g']],
                    ['label' => '1kg', 'price' => 14.99, 'attrs' => ['Size' => '1kg']],
                ]],

            ['name' => 'Green Tea Bags', 'category' => 'beverages', 'sub' => 'water', 'brand' => 'puresip', 'unit' => 'pc',
                'short' => 'Premium green tea bags.', 'description' => 'Delicate green tea for everyday brewing.',
                'flags' => ['trending', 'bestseller'], 'discount' => 10, 'age_days' => 24,
                'image' => 'photo-1495474472287-4d71bcdd2085',
                'variations' => [
                    ['label' => '25 bags', 'price' => 3.49, 'attrs' => ['Pack' => '25']],
                    ['label' => '50 bags', 'price' => 5.99, 'attrs' => ['Pack' => '50']],
                    ['label' => '100 bags', 'price' => 9.99, 'attrs' => ['Pack' => '100']],
                ]],
        ];

        $catalog = array_merge($single, $multi);

        if (count($catalog) !== 50) {
            throw new \RuntimeException('Demo catalog must contain exactly 50 products, got ' . count($catalog));
        }

        $singleCount = count(array_filter($catalog, fn ($p) => count($p['variations']) === 1));
        if ($singleCount !== 15) {
            throw new \RuntimeException("Expected 15 single-variation products, got {$singleCount}");
        }

        return $catalog;
    }
}
