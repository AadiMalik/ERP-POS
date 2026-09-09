<?php

namespace App\Support\Tax;

/**
 * Single shared rounding/formula for turning a taxable amount + a tax
 * percent into a tax amount. Used by every return/reversal service that
 * carries forward a line's already-stamped tax percent from the original
 * document (a return must always use the rate the original sale/purchase
 * was taxed at, never today's rate - see TaxSettingResolverService for
 * where that rate is first resolved at draft-save/posting time).
 */
class TaxCalculator
{
    /**
     * Exclusive (default): tax is computed on top of the taxable amount.
     * Inclusive: the taxable amount already contains tax, so this backs the
     * tax portion out of it instead - the customer-facing price never
     * changes between modes, only how much of it is reported as tax.
     */
    public static function lineTax(float $taxable, float $percent, string $tax_type = 'exclusive'): float
    {
        if ($percent <= 0) {
            return 0.0;
        }

        if ($tax_type === 'inclusive') {
            return round($taxable - ($taxable / (1 + $percent / 100)), 3);
        }

        return round($taxable * $percent / 100, 3);
    }

    /**
     * The line/order total given its taxable base and already-computed tax
     * amount - exclusive tax is additive (total = taxable + tax); inclusive
     * tax is already inside the taxable amount, so the total is the taxable
     * amount itself.
     */
    public static function lineTotal(float $taxable, float $tax_amount, string $tax_type = 'exclusive'): float
    {
        return $tax_type === 'inclusive' ? round($taxable, 3) : round($taxable + $tax_amount, 3);
    }
}
