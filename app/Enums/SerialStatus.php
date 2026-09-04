<?php

namespace App\Enums;

/**
 * Lifecycle status of a single product_variation_serial_numbers row.
 * "available" is the only status a serial can be picked from for a
 * transfer/sale/return/loss. See App\Services\Concrete\Admin\ProductVariationSerialService.
 */
class SerialStatus
{
    const AVAILABLE = 'available';
    const IN_TRANSIT = 'in_transit';
    const SOLD = 'sold';
    const RETURNED_TO_SUPPLIER = 'returned_to_supplier';
    const DAMAGED = 'damaged';
    const WASTED = 'wasted';
    const EXPIRED = 'expired';
    const UNDER_REPAIR = 'under_repair';
    const REPLACED = 'replaced';
    const DECOMMISSIONED = 'decommissioned';

    public static function getOptions()
    {
        return [
            self::AVAILABLE => 'Available',
            self::IN_TRANSIT => 'In Transit',
            self::SOLD => 'Sold',
            self::RETURNED_TO_SUPPLIER => 'Returned to Supplier',
            self::DAMAGED => 'Damaged',
            self::WASTED => 'Wasted',
            self::EXPIRED => 'Expired',
            self::UNDER_REPAIR => 'Under Repair',
            self::REPLACED => 'Replaced',
            self::DECOMMISSIONED => 'Decommissioned',
        ];
    }

    /**
     * Statuses that exclude a unit from "available for pick" pools but do
     * NOT mean it's gone forever (e.g. under repair can come back).
     */
    public static function terminalStatuses()
    {
        return [self::RETURNED_TO_SUPPLIER, self::REPLACED, self::DECOMMISSIONED];
    }
}
