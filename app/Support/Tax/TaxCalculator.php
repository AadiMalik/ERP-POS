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
     *
     * Inclusive + tax_discount_percent: the price is treated as inclusive of
     * (applied tax + tax discount). Cash vs card rates can differ; the
     * leftover rate (max(cash, card) - applied) is the tax discount. The
     * customer still pays the same inclusive price; only the split between
     * tax collected and tax discount changes.
     */
    public static function lineTax(float $taxable, float $percent, string $tax_type = 'exclusive', float $tax_discount_percent = 0.0): float
    {
        return self::lineBreakdown($taxable, $percent, $tax_type, $tax_discount_percent)['tax_amount'];
    }

    public static function lineTaxDiscount(float $taxable, float $percent, string $tax_type = 'exclusive', float $tax_discount_percent = 0.0): float
    {
        return self::lineBreakdown($taxable, $percent, $tax_type, $tax_discount_percent)['tax_discount_amount'];
    }

    /**
     * @return array{tax_amount: float, tax_discount_amount: float}
     */
    public static function lineBreakdown(float $taxable, float $percent, string $tax_type = 'exclusive', float $tax_discount_percent = 0.0): array
    {
        $tax_discount_percent = $tax_type === 'inclusive' ? max(0.0, $tax_discount_percent) : 0.0;

        if ($tax_type !== 'inclusive') {
            return [
                'tax_amount' => $percent > 0 ? round($taxable * $percent / 100, 3) : 0.0,
                'tax_discount_amount' => 0.0,
            ];
        }

        $combined = $percent + $tax_discount_percent;

        if ($combined <= 0) {
            return [
                'tax_amount' => 0.0,
                'tax_discount_amount' => 0.0,
            ];
        }

        // No tax discount: keep the original extract-at-applied-rate formula
        // bit-for-bit so existing inclusive orders are unchanged.
        if ($tax_discount_percent <= 0) {
            return [
                'tax_amount' => $percent > 0 ? round($taxable - ($taxable / (1 + $percent / 100)), 3) : 0.0,
                'tax_discount_amount' => 0.0,
            ];
        }

        $base = $taxable / (1 + $combined / 100);
        $tax_amount = round($base * $percent / 100, 3);
        $tax_discount_amount = round($taxable - $base - $tax_amount, 3);

        if ($tax_discount_amount < 0) {
            $tax_discount_amount = 0.0;
        }

        return [
            'tax_amount' => $tax_amount,
            'tax_discount_amount' => $tax_discount_amount,
        ];
    }

    /**
     * The line/order total given its taxable base and already-computed tax
     * amount - exclusive tax is additive (total = taxable + tax); inclusive
     * tax is already inside the taxable amount, so the total is the taxable
     * amount itself. Tax discount never changes this: it is only a split
     * of the inclusive price, not an extra charge or a reduction of what
     * the customer pays.
     */
    public static function lineTotal(float $taxable, float $tax_amount, string $tax_type = 'exclusive'): float
    {
        return $tax_type === 'inclusive' ? round($taxable, 3) : round($taxable + $tax_amount, 3);
    }
}
