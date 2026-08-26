<?php

namespace App\Services\Concrete\Api;

use App\Enums\Status;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Wishlist;
use Exception;

class WishlistService
{
    /**
     * List wishlist entries for a user within a business, with product info.
     */
    public function list(int $user_id, string $business_id): array
    {
        $rows = Wishlist::where('user_id', $user_id)
            ->where('business_id', $business_id)
            ->orderByDesc('date_created')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $product_ids = $rows->pluck('product_id')->unique()->values()->all();
        $products = Product::where('business_id', $business_id)
            ->whereIn('product_id', $product_ids)
            ->where('is_deleted', 0)
            ->with([
                'productImages' => function ($q) {
                    $q->orderByDesc('is_default')->orderBy('sorting');
                },
                'productVariations',
                'brand:brand_id,name',
            ])
            ->get()
            ->keyBy('product_id');

        $items = [];
        foreach ($rows as $row) {
            $product = $products->get($row->product_id);
            if (!$product || $product->status !== Status::ACTIVE || !$product->is_website_visible) {
                continue;
            }

            $variation = null;
            if ($row->product_variation_id) {
                $variation = $product->productVariations
                    ->firstWhere('product_variation_id', $row->product_variation_id);
            }

            $items[] = [
                'id' => $row->wishlist_id,
                'product_id' => $row->product_id,
                'product_variation_id' => $row->product_variation_id,
                'is_variation' => (bool) $row->product_variation_id,
                'product' => [
                    'id' => $product->product_id,
                    'slug' => $product->slug,
                    'name' => $product->name,
                    'brand' => $product->brand->name ?? null,
                    'images' => $product->productImages->pluck('image_url')->values()->all(),
                    'variation_label' => $variation->name ?? null,
                ],
                'date_created' => optional($row->date_created)->toIso8601String(),
            ];
        }

        return $items;
    }

    public function add(int $user_id, string $business_id, string $product_id, ?string $product_variation_id = null): array
    {
        $product = Product::where('business_id', $business_id)
            ->where('product_id', $product_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->where('is_website_visible', 1)
            ->first();

        if (!$product) {
            throw new Exception('Product not found.');
        }

        if ($product_variation_id) {
            $exists = ProductVariation::where('product_id', $product_id)
                ->where('product_variation_id', $product_variation_id)
                ->where('is_deleted', 0)
                ->exists();

            if (!$exists) {
                throw new Exception('Product variation not found.');
            }
        }

        $item_key = Wishlist::makeItemKey($product_id, $product_variation_id);

        $row = Wishlist::firstOrCreate(
            [
                'user_id' => $user_id,
                'business_id' => $business_id,
                'item_key' => $item_key,
            ],
            [
                'wishlist_id' => generateUuid(),
                'product_id' => $product_id,
                'product_variation_id' => $product_variation_id,
                'date_created' => now(),
            ]
        );

        return [
            'id' => $row->wishlist_id,
            'product_id' => $row->product_id,
            'product_variation_id' => $row->product_variation_id,
            'is_wishlisted' => true,
        ];
    }

    public function remove(int $user_id, string $business_id, string $product_id, ?string $product_variation_id = null): void
    {
        $item_key = Wishlist::makeItemKey($product_id, $product_variation_id);

        $deleted = Wishlist::where('user_id', $user_id)
            ->where('business_id', $business_id)
            ->where('item_key', $item_key)
            ->delete();

        if (!$deleted) {
            throw new Exception('Wishlist item not found.');
        }
    }

    public function toggle(int $user_id, string $business_id, string $product_id, ?string $product_variation_id = null): array
    {
        $item_key = Wishlist::makeItemKey($product_id, $product_variation_id);
        $existing = Wishlist::where('user_id', $user_id)
            ->where('business_id', $business_id)
            ->where('item_key', $item_key)
            ->first();

        if ($existing) {
            $existing->delete();

            return [
                'product_id' => $product_id,
                'product_variation_id' => $product_variation_id,
                'is_wishlisted' => false,
            ];
        }

        return $this->add($user_id, $business_id, $product_id, $product_variation_id);
    }

    public function status(int $user_id, string $business_id, string $product_id, ?string $product_variation_id = null): array
    {
        $product_wishlisted = Wishlist::where('user_id', $user_id)
            ->where('business_id', $business_id)
            ->where('product_id', $product_id)
            ->whereNull('product_variation_id')
            ->exists();

        $variation_wishlisted = false;
        if ($product_variation_id) {
            $variation_wishlisted = Wishlist::where('user_id', $user_id)
                ->where('business_id', $business_id)
                ->where('product_id', $product_id)
                ->where('product_variation_id', $product_variation_id)
                ->exists();
        }

        $wishlisted_variation_ids = Wishlist::where('user_id', $user_id)
            ->where('business_id', $business_id)
            ->where('product_id', $product_id)
            ->whereNotNull('product_variation_id')
            ->pluck('product_variation_id')
            ->values()
            ->all();

        return [
            'product_id' => $product_id,
            'product_variation_id' => $product_variation_id,
            'is_wishlisted' => $product_variation_id ? $variation_wishlisted : $product_wishlisted,
            'is_product_wishlisted' => $product_wishlisted,
            'is_variation_wishlisted' => $variation_wishlisted,
            'wishlisted_variation_ids' => $wishlisted_variation_ids,
        ];
    }

    /**
     * Bulk wishlist flags for product listing/detail enrichment.
     *
     * @return array{product_ids: array<string, bool>, variation_ids: array<string, bool>}
     */
    public function flagsForUser(int $user_id, string $business_id, array $product_ids = []): array
    {
        $query = Wishlist::where('user_id', $user_id)->where('business_id', $business_id);

        if (!empty($product_ids)) {
            $query->whereIn('product_id', $product_ids);
        }

        $rows = $query->get(['product_id', 'product_variation_id']);

        $product_ids_map = [];
        $variation_ids_map = [];

        foreach ($rows as $row) {
            if ($row->product_variation_id) {
                $variation_ids_map[$row->product_variation_id] = true;
            } else {
                $product_ids_map[$row->product_id] = true;
            }
        }

        return [
            'product_ids' => $product_ids_map,
            'variation_ids' => $variation_ids_map,
        ];
    }
}
