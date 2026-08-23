<?php

namespace App\Support\Tax;

/**
 * Single shared rounding/formula for turning a taxable amount + a tax
 * percent into a tax amount. Used by every return/reversal service that
 * carries forward a line's already-stamped tax percent from the original
 * document (a return must always use the rate the original sale/purchase
 * was taxed at, never today's rate - see OrderService::resolveTaxPercent()
 * for where that rate is first resolved at draft-save/posting time).
 */
class TaxCalculator
{
    public static function lineTax(float $taxable, float $percent): float
    {
        return round($taxable * $percent / 100, 3);
    }
}
