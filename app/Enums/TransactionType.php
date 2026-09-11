<?php

namespace App\Enums;

class  TransactionType
{
    const OPENING = 'opening';
    const PURCHASE = 'purchase';
    const PURCHASE_RETURN = 'purchase_return';
    const SALE = 'sale';
    const SALE_RETURN = 'sale_return';
    const STOCK_TAKE_INCREASE = 'stock_take_increase';
    const STOCK_TAKE_DECREASE = 'stock_take_decrease';
    const ADJUSTMENT = 'adjustment';
    const DAMAGE = 'damage';
    const EXPIRED = 'expired';
    const WASTAGE = 'wastage';
    const CONSUMPTION = 'consumption';
    const GIFT = 'gift';
    const SAMPLE = 'sample';
    const TRANSFER_IN = 'transfer_in';
    const TRANSFER_OUT = 'transfer_out';
    const PRODUCTION_IN = 'production_in';
    const PRODUCTION_OUT = 'production_out';
    const COST_ADJUSTMENT = 'cost_price_adjustment';
    const COMPLIMENTARY = 'complimentary';
    const COMPLIMENTARY_RETURN = 'complimentary_return';

    public static function getOptions()
    {
        return [
            self::OPENING => 'Opening',
            self::PURCHASE => 'Purchase',
            self::PURCHASE_RETURN => 'Purchase Return',
            self::SALE => 'Sale',
            self::SALE_RETURN => 'Sale Return',
            self::STOCK_TAKE_INCREASE => 'Stock Take Increase',
            self::STOCK_TAKE_DECREASE => 'Stock Take Decrease',
            self::ADJUSTMENT => 'Adjustment',
            self::DAMAGE => 'Damage',
            self::EXPIRED => 'Expired',
            self::WASTAGE => 'Wastage',
            self::CONSUMPTION => 'Consumption',
            self::GIFT => 'Gift',
            self::SAMPLE => 'Sample',
            self::TRANSFER_IN => 'Transfer In',
            self::TRANSFER_OUT => 'Transfer Out',
            self::PRODUCTION_IN => 'Production In',
            self::PRODUCTION_OUT => 'Production Out',
            self::COST_ADJUSTMENT => 'Cost Price Adjustment',
            self::COMPLIMENTARY => 'Complimentary',
            self::COMPLIMENTARY_RETURN => 'Complimentary Return',
        ];
    }

    /**
     * Transaction types that increase stock (Stock In).
     *
     * COST_ADJUSTMENT is included here even though it never moves quantity
     * (base_quantity is always 0): ProductVariationStockService::recomputeLedger()
     * replays inbound transactions as
     * avg_price = ((quantity * avg_price) + total_price) / new_quantity, and
     * with base_quantity = 0 (new_quantity = quantity, no movement) that
     * reduces to avg_price + total_price / quantity - exactly the corrected
     * cost this transaction type carries in total_price. Classifying it as
     * outbound instead would make a later replay silently drop the
     * adjustment's effect on avg_price.
     */
    public static function inboundTypes()
    {
        return [
            self::OPENING,
            self::PURCHASE,
            self::SALE_RETURN,
            self::STOCK_TAKE_INCREASE,
            self::TRANSFER_IN,
            self::PRODUCTION_IN,
            self::COST_ADJUSTMENT,
            self::COMPLIMENTARY_RETURN,
        ];
    }

    /**
     * True if the given transaction type increases stock (Stock In) rather
     * than decreasing it (Stock Out).
     */
    public static function isInbound($type)
    {
        return in_array($type, self::inboundTypes(), true);
    }
}
