<?php

namespace App\Services\Concrete\Admin;

use App\Models\ProductVariation;
use App\Models\ProductVariationPrice;

/**
 * Resolves what a Product Variation should sell for under a given Sale Type
 * (Retail/Wholesale/Branch/Promotional/Online/custom - see SaleType). Single
 * source of truth used by both the POS search/browse preview
 * (OrderService::applyResolvedPricing()) and the authoritative server-side
 * persist path (OrderService::saveLinesAndComputeTotals()), so both always
 * agree on the same price/discount for a given (variation, sale_type) pair.
 *
 * Deliberately simple - no branch/priority/schedule tiers (that was the
 * discarded Price List design). A variation either has a configured price row
 * for a sale type, or falls back to its own sale_price.
 */
class VariationPricingService
{
    /**
     * @param string[] $variationIds
     * @return array<string,array{price:float,discount_percentage:float,minimum_selling_price:?float}>
     */
    public function resolveBulk(array $variationIds, ?string $saleTypeId): array
    {
        $variationIds = array_values(array_unique(array_filter($variationIds)));

        if (empty($variationIds)) {
            return [];
        }

        $variations = ProductVariation::with('discountSaleTypes:sale_types.sale_type_id')
            ->whereIn('product_variation_id', $variationIds)
            ->get()
            ->keyBy('product_variation_id');

        $prices = collect();

        if (!empty($saleTypeId)) {
            $prices = ProductVariationPrice::whereIn('product_variation_id', $variationIds)
                ->where('sale_type_id', $saleTypeId)
                ->get()
                ->keyBy('product_variation_id');
        }

        $results = [];

        foreach ($variationIds as $variation_id) {
            $variation = $variations->get($variation_id);

            if (!$variation) {
                continue;
            }

            $price_row = $prices->get($variation_id);

            $discount_applies = (bool) $variation->discount_apply_all
                || (!empty($saleTypeId) && $variation->discountSaleTypes->pluck('sale_type_id')->contains($saleTypeId));

            $results[$variation_id] = [
                'price' => $price_row ? (float) $price_row->price : (float) $variation->sale_price,
                'discount_percentage' => $discount_applies ? (float) $variation->discount_percentage : 0.0,
                'minimum_selling_price' => $variation->minimum_selling_price !== null ? (float) $variation->minimum_selling_price : null,
            ];
        }

        return $results;
    }

    public function resolve(ProductVariation $variation, ?string $saleTypeId): array
    {
        $result = $this->resolveBulk([$variation->product_variation_id], $saleTypeId);

        return $result[$variation->product_variation_id] ?? [
            'price' => (float) $variation->sale_price,
            'discount_percentage' => 0.0,
            'minimum_selling_price' => $variation->minimum_selling_price !== null ? (float) $variation->minimum_selling_price : null,
        ];
    }

    /**
     * Whether a resolved (post-discount) net price falls below the given
     * minimum selling price floor. Null floor = no restriction.
     */
    public function isBelowFloor(?float $minimum_selling_price, float $net_price): bool
    {
        if ($minimum_selling_price === null) {
            return false;
        }

        return round($net_price, 3) < round($minimum_selling_price, 3);
    }
}
