<?php

namespace App\Services\Concrete\Api;

use App\Models\Product;
use App\Models\ProductShare;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Records "share this product" clicks from the website and mobile app
 * (both call this same service - see Api\ProductController::share() and
 * Api\Mobile\ProductController::share()). Powers the product-level share
 * count, the per-platform breakdown, and the share log shown on the admin
 * Product edit screen / Product Shares report.
 */
class ProductShareService
{
    /** Only these are accepted - keeps the platform column meaningful/queryable. */
    public const PLATFORMS = [
        'whatsapp', 'facebook', 'linkedin', 'twitter', 'telegram',
        'pinterest', 'email', 'copy_link', 'native',
    ];

    /**
     * $customer_id is null for a guest shopper - the share is still logged,
     * just without an identity (shown as "Guest" on the admin side).
     */
    public function record(string $business_id, string $product_id, ?int $customer_id, string $platform): array
    {
        $platform = strtolower(trim($platform));
        if (!in_array($platform, self::PLATFORMS, true)) {
            throw new Exception('Unsupported share platform.');
        }

        $product = Product::where('product_id', $product_id)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->first();

        if (!$product) {
            throw new Exception('Product not found.');
        }

        DB::beginTransaction();
        try {
            ProductShare::create([
                'product_share_id' => generateUuid(),
                'business_id' => $business_id,
                'product_id' => $product_id,
                'customer_id' => $customer_id,
                'platform' => $platform,
                'date_created' => now(),
            ]);

            $product->increment('share_count');

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return ['share_count' => $product->share_count];
    }
}
