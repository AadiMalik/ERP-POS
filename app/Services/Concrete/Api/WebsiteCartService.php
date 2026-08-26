<?php

namespace App\Services\Concrete\Api;

use App\Enums\Status;
use App\Models\Branch;
use App\Models\BusinessSetting;
use App\Models\InventorySetting;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductVariationStock;
use App\Models\SaleType;
use App\Models\Warehouse;
use App\Models\WebsiteCart;
use App\Models\WebsiteCartItem;
use App\Services\Concrete\Admin\VariationPricingService;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Authenticated website cart - server-authoritative prices/stock using the
 * business default sale type (same source as the product listing API).
 */
class WebsiteCartService
{
    protected $pricing_engine;

    public function __construct(VariationPricingService $pricing_engine)
    {
        $this->pricing_engine = $pricing_engine;
    }

    public function getOrCreateCart(int $user_id, string $business_id, ?string $branch_id = null): WebsiteCart
    {
        $cart = WebsiteCart::firstOrCreate(
            [
                'business_id' => $business_id,
                'user_id' => $user_id,
            ],
            [
                'cart_id' => generateUuid(),
                'branch_id' => $branch_id,
                'date_created' => now(),
                'date_updated' => now(),
            ]
        );

        if ($branch_id && $cart->branch_id !== $branch_id) {
            $cart->update(['branch_id' => $branch_id, 'date_updated' => now()]);
        }

        return $cart;
    }

    public function getCart(int $user_id, string $business_id, ?string $branch_id = null): array
    {
        $cart = $this->getOrCreateCart($user_id, $business_id, $branch_id);

        return $this->buildCartPayload($cart);
    }

    public function addItem(
        int $user_id,
        string $business_id,
        string $product_id,
        string $product_variation_id,
        float $quantity = 1,
        ?string $branch_id = null
    ): array {
        if ($quantity <= 0) {
            throw new Exception('Quantity must be greater than zero.');
        }

        $cart = $this->getOrCreateCart($user_id, $business_id, $branch_id);
        $context = $this->resolveFulfillmentContext($business_id, $branch_id ?? $cart->branch_id);
        $sale_type_id = $this->resolveDefaultSaleTypeId($business_id);

        $validated = $this->validateLine(
            $business_id,
            $context['warehouse_id'],
            $product_id,
            $product_variation_id,
            $quantity,
            $sale_type_id,
            true
        );

        $existing = WebsiteCartItem::where('cart_id', $cart->cart_id)
            ->where('product_variation_id', $product_variation_id)
            ->first();

        $new_qty = $existing
            ? (float) $existing->quantity + $quantity
            : $quantity;

        $this->validateLine(
            $business_id,
            $context['warehouse_id'],
            $product_id,
            $product_variation_id,
            $new_qty,
            $sale_type_id,
            true
        );

        if ($existing) {
            $existing->update([
                'quantity' => $new_qty,
                'date_updated' => now(),
            ]);
        } else {
            WebsiteCartItem::create([
                'cart_item_id' => generateUuid(),
                'cart_id' => $cart->cart_id,
                'product_id' => $product_id,
                'product_variation_id' => $product_variation_id,
                'quantity' => $new_qty,
                'date_created' => now(),
                'date_updated' => now(),
            ]);
        }

        $cart->update(['branch_id' => $context['branch_id'], 'date_updated' => now()]);

        return $this->buildCartPayload($cart->fresh());
    }

    public function updateItem(
        int $user_id,
        string $business_id,
        string $cart_item_id,
        float $quantity,
        ?string $branch_id = null
    ): array {
        $cart = $this->getOrCreateCart($user_id, $business_id, $branch_id);
        $item = WebsiteCartItem::where('cart_id', $cart->cart_id)
            ->where('cart_item_id', $cart_item_id)
            ->first();

        if (!$item) {
            throw new Exception('Cart item not found.');
        }

        if ($quantity <= 0) {
            $item->delete();
            $cart->update(['date_updated' => now()]);

            return $this->buildCartPayload($cart->fresh());
        }

        $context = $this->resolveFulfillmentContext($business_id, $branch_id ?? $cart->branch_id);
        $sale_type_id = $this->resolveDefaultSaleTypeId($business_id);

        $this->validateLine(
            $business_id,
            $context['warehouse_id'],
            $item->product_id,
            $item->product_variation_id,
            $quantity,
            $sale_type_id,
            true
        );

        $item->update([
            'quantity' => $quantity,
            'date_updated' => now(),
        ]);
        $cart->update(['branch_id' => $context['branch_id'], 'date_updated' => now()]);

        return $this->buildCartPayload($cart->fresh());
    }

    public function removeItem(int $user_id, string $business_id, string $cart_item_id): array
    {
        $cart = $this->getOrCreateCart($user_id, $business_id);
        $deleted = WebsiteCartItem::where('cart_id', $cart->cart_id)
            ->where('cart_item_id', $cart_item_id)
            ->delete();

        if (!$deleted) {
            throw new Exception('Cart item not found.');
        }

        $cart->update(['date_updated' => now()]);

        return $this->buildCartPayload($cart->fresh());
    }

    public function clear(int $user_id, string $business_id): array
    {
        $cart = $this->getOrCreateCart($user_id, $business_id);
        WebsiteCartItem::where('cart_id', $cart->cart_id)->delete();
        $cart->update(['date_updated' => now()]);

        return $this->buildCartPayload($cart->fresh());
    }

    /**
     * Build the storefront cart response with live prices/stock.
     * Invalid/unavailable lines are dropped so the cart never shows stale items.
     */
    public function buildCartPayload(WebsiteCart $cart): array
    {
        $cart->load(['items']);
        $context = $this->resolveFulfillmentContext($cart->business_id, $cart->branch_id);
        $sale_type_id = $this->resolveDefaultSaleTypeId($cart->business_id);
        $sale_type = $sale_type_id
            ? SaleType::where('sale_type_id', $sale_type_id)->first(['sale_type_id', 'name', 'code'])
            : null;

        $items = [];
        $subtotal = 0.0;
        $discount_total = 0.0;

        foreach ($cart->items as $item) {
            try {
                $line = $this->validateLine(
                    $cart->business_id,
                    $context['warehouse_id'],
                    $item->product_id,
                    $item->product_variation_id,
                    (float) $item->quantity,
                    $sale_type_id,
                    false
                );
            } catch (Exception $e) {
                // Drop unavailable lines rather than failing the whole cart read.
                $item->delete();
                continue;
            }

            $qty = (float) $item->quantity;
            if ($line['is_track_stock'] && $line['available_stock'] !== null && $qty > $line['available_stock']) {
                $qty = max(0, (float) $line['available_stock']);
                if ($qty <= 0) {
                    $item->delete();
                    continue;
                }
                $item->update(['quantity' => $qty, 'date_updated' => now()]);
            }

            $unit_price = $line['unit_price'];
            $line_subtotal = round($unit_price * $qty, 3);
            $line_discount = round($line_subtotal * ($line['discount_percentage'] / 100), 3);
            $line_total = round($line_subtotal - $line_discount, 3);

            $subtotal += $line_subtotal;
            $discount_total += $line_discount;

            $items[] = [
                'cart_item_id' => $item->cart_item_id,
                'product_id' => $item->product_id,
                'product_variation_id' => $item->product_variation_id,
                'quantity' => $qty,
                'name' => $line['product_name'],
                'slug' => $line['slug'],
                'image' => $line['image'],
                'variation' => $line['variation_name'],
                'unit_price' => $unit_price,
                'unit_old_price' => $line['unit_old_price'],
                'discount_percentage' => $line['discount_percentage'],
                'line_subtotal' => $line_subtotal,
                'line_discount' => $line_discount,
                'line_total' => $line_total,
                'is_track_stock' => $line['is_track_stock'],
                'available_stock' => $line['available_stock'],
                'in_stock' => !$line['is_track_stock'] || ($line['available_stock'] ?? 0) > 0,
            ];
        }

        $tax_percent = $this->resolveTaxPercent($cart->business_id);
        $taxable = max(0, $subtotal - $discount_total);
        $tax_amount = round($taxable * $tax_percent / 100, 3);
        $total = round($taxable + $tax_amount, 3);

        return [
            'cart_id' => $cart->cart_id,
            'business_id' => $cart->business_id,
            'branch_id' => $context['branch_id'],
            'warehouse_id' => $context['warehouse_id'],
            'sale_type' => $sale_type ? [
                'id' => $sale_type->sale_type_id,
                'name' => $sale_type->name,
                'code' => $sale_type->code,
            ] : null,
            'items' => $items,
            'item_count' => collect($items)->sum('quantity'),
            'totals' => [
                'subtotal' => round($subtotal, 3),
                'discount' => round($discount_total, 3),
                'tax_percent' => $tax_percent,
                'tax' => $tax_amount,
                'shipping' => 0,
                'total' => $total,
            ],
        ];
    }

    /**
     * Validate a product/variation for the cart against business, visibility,
     * availability, stock, and default sale-type price.
     */
    public function validateLine(
        string $business_id,
        ?string $warehouse_id,
        string $product_id,
        string $product_variation_id,
        float $quantity,
        ?string $sale_type_id,
        bool $enforce_stock
    ): array {
        $product = Product::with([
            'productImages' => function ($q) {
                $q->orderByDesc('is_default')->orderBy('sorting');
            },
        ])
            ->where('business_id', $business_id)
            ->where('product_id', $product_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->where('is_website_visible', 1)
            ->first();

        if (!$product) {
            throw new Exception('Product is not available.');
        }

        $variation = ProductVariation::where('product_id', $product_id)
            ->where('product_variation_id', $product_variation_id)
            ->where('is_deleted', 0)
            ->first();

        if (!$variation) {
            throw new Exception('Product variation is not available.');
        }

        if ($variation->status && $variation->status !== Status::ACTIVE) {
            throw new Exception('Product variation is inactive.');
        }

        $resolved = $this->pricing_engine->resolve($variation, $sale_type_id);
        $base_price = (float) ($resolved['price'] ?? $variation->sale_price);
        $discount_pct = (float) ($resolved['discount_percentage'] ?? 0);
        $unit_price = $discount_pct > 0
            ? round($base_price * (1 - $discount_pct / 100), 3)
            : round($base_price, 3);

        if ($unit_price <= 0) {
            throw new Exception('Product price is not configured for the website sale type.');
        }

        $is_tracked = (bool) $product->is_track_stock;
        $available = null;

        if ($is_tracked) {
            $available = $this->getAvailableStock($business_id, $warehouse_id, $product_id, $product_variation_id);
            $allow_negative = (bool) (InventorySetting::where('business_id', $business_id)->value('negative_stock') ?? false);

            if ($enforce_stock && !$allow_negative && $quantity > $available) {
                throw new Exception(sprintf(
                    'Insufficient stock for "%s". Available: %s, requested: %s.',
                    $variation->name ?: $product->name,
                    $available,
                    $quantity
                ));
            }
        }

        $images = $product->productImages->pluck('image_url')->values()->all();

        return [
            'product_id' => $product_id,
            'product_variation_id' => $product_variation_id,
            'product_name' => $product->name,
            'slug' => $product->slug,
            'variation_name' => $variation->name,
            'image' => $images[0] ?? null,
            'unit_price' => $unit_price,
            'unit_old_price' => $discount_pct > 0 ? round($base_price, 3) : null,
            'discount_percentage' => $discount_pct,
            'is_track_stock' => $is_tracked,
            'available_stock' => $available,
            'unit_id' => $variation->base_unit_id ?? $variation->sale_unit_id ?? $variation->unit_id,
        ];
    }

    public function resolveDefaultSaleTypeId(string $business_id): ?string
    {
        $sale_type = SaleType::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->first();

        return $sale_type->sale_type_id ?? null;
    }

    public function resolveFulfillmentContext(string $business_id, ?string $branch_id = null): array
    {
        $branch = null;

        if ($branch_id) {
            $branch = Branch::where('business_id', $business_id)
                ->where('branch_id', $branch_id)
                ->where('status', Status::ACTIVE)
                ->where('is_deleted', 0)
                ->first();
        }

        if (!$branch) {
            $branch = Branch::where('business_id', $business_id)
                ->where('status', Status::ACTIVE)
                ->where('is_deleted', 0)
                ->orderBy('name')
                ->first();
        }

        if (!$branch) {
            throw new Exception('No active branch is configured for this business.');
        }

        $warehouse = Warehouse::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->where(function ($q) use ($branch) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branch->branch_id);
            })
            ->orderByRaw('CASE WHEN branch_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('name')
            ->first();

        if (!$warehouse) {
            throw new Exception('No active warehouse is configured for this business/branch.');
        }

        return [
            'branch_id' => $branch->branch_id,
            'warehouse_id' => $warehouse->warehouse_id,
        ];
    }

    public function resolveTaxPercent(string $business_id): float
    {
        $setting = BusinessSetting::where('business_id', $business_id)->first();

        return (float) ($setting->overall_tax_rate ?? 0);
    }

    protected function getAvailableStock(
        string $business_id,
        ?string $warehouse_id,
        string $product_id,
        string $product_variation_id
    ): float {
        if (empty($warehouse_id)) {
            return 0.0;
        }

        return (float) (ProductVariationStock::where('business_id', $business_id)
            ->where('warehouse_id', $warehouse_id)
            ->where('product_id', $product_id)
            ->where('product_variation_id', $product_variation_id)
            ->value('quantity') ?? 0);
    }
}
