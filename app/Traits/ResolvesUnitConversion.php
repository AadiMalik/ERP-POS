<?php

namespace App\Traits;

use App\Models\ProductVariationUnitConversion;
use Exception;

/**
 * Resolve a (product_variation_id, unit_id) pair to the variation's base-unit
 * conversion factor, exactly the same lookup every existing stock-writing
 * module (Purchase/GRN/Order) relies on via a copied
 * product_variation_unit_conversion_id + stored conversion_factor - see
 * GrnService::applyGrnApproval() ("$conversion_factor = $detail->
 * conversion_factor > 0 ? ... : 1"). Recipe items/plans/productions don't
 * have a pre-existing detail row to copy a factor from, so this resolves it
 * fresh from product_variation_unit_conversions each time. Shared by
 * ProductRecipeService (validation) and ManufacturingPlanService/
 * ProductionService (actual base-quantity math) so all three agree.
 */
trait ResolvesUnitConversion
{
    /**
     * @return array{conversion_factor: float, product_variation_unit_conversion_id: ?string}
     * @throws Exception when $unitId is neither the variation's base unit nor
     *   backed by a configured conversion row.
     */
    protected function resolveConversionFactor(string $productVariationId, ?string $unitId, string $baseUnitId): array
    {
        if (empty($unitId) || $unitId === $baseUnitId) {
            return ['conversion_factor' => 1.0, 'product_variation_unit_conversion_id' => null];
        }

        $conversion = ProductVariationUnitConversion::where('product_variation_id', $productVariationId)
            ->where('from_unit_id', $unitId)
            ->where('is_deleted', 0)
            ->first();

        if (!$conversion || (float) $conversion->conversion_factor <= 0) {
            throw new Exception('No unit conversion is configured from the selected unit to this item\'s base unit. Configure it under Unit Conversion first.');
        }

        return [
            'conversion_factor' => (float) $conversion->conversion_factor,
            'product_variation_unit_conversion_id' => $conversion->product_variation_unit_conversion_id,
        ];
    }
}
