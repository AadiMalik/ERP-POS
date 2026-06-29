<?php

namespace App\Enums;

class  ReferenceType
{
    const OPENING_STOCK = "opening_stock";
    const PURCHASE = "purchase";
    const PURCHASE_RETURN = "purchase_return";
    const GRN = 'grn';
    const SALE = 'sale';
    const SALE_RETURN = 'sale_return';
    const STOCK_TAKING = 'stock_taking';
    const DAMAGE_NOTE = 'damage_note';
    const EXPIRY_NOTE = 'expiry_note';
    const WASTAGE_NOTE = 'wastage_note';
    const STOCK_TRANSFER = 'stock_transfer';
    const PRODUCTION = 'production';
    const CONSUMPTION = 'consumption';
    const GIFT = 'gift';
    const SAMPLE = 'sample';
    const MANUAL = 'manual';

    public static function getOptions()
    {
        return [
            self::OPENING_STOCK => 'Opening Stock',
            self::PURCHASE => 'Purchase',
            self::PURCHASE_RETURN => 'Purchase Return',
            self::GRN => 'GRN',
            self::SALE => 'Sale',
            self::SALE_RETURN => 'Sale Return',
            self::STOCK_TAKING => 'Stock Taking',
            self::DAMAGE_NOTE => 'Damage Note',
            self::EXPIRY_NOTE => 'Expiry Note',
            self::WASTAGE_NOTE => 'Wastage Note',
            self::STOCK_TRANSFER => 'Stock Transfer',
            self::PRODUCTION => 'Production',
            self::CONSUMPTION => 'Consumption',
            self::GIFT => 'Gift',
            self::SAMPLE => 'Sample',
            self::MANUAL => 'Manual',
        ];
    }
}
