<?php

namespace App\Enums;

/**
 * Event types recorded in product_variation_serial_movements - the
 * append-only audit trail for a single serialized unit.
 */
class SerialMovementEventType
{
    const PURCHASED = 'purchased';
    const OPENING_STOCK = 'opening_stock';
    const TRANSFER_SENT = 'transfer_sent';
    const TRANSFER_RECEIVED = 'transfer_received';
    const SOLD = 'sold';
    const SALE_RETURNED = 'sale_returned';
    const PURCHASE_RETURNED = 'purchase_returned';
    const DAMAGED = 'damaged';
    const WASTED = 'wasted';
    const EXPIRED = 'expired';
    const SENT_FOR_REPAIR = 'sent_for_repair';
    const RETURNED_FROM_REPAIR = 'returned_from_repair';
    const REPLACED = 'replaced';
    const DECOMMISSIONED = 'decommissioned';
    const ADDED_MANUALLY = 'added_manually';

    public static function getOptions()
    {
        return [
            self::PURCHASED => 'Purchased',
            self::OPENING_STOCK => 'Opening Stock',
            self::TRANSFER_SENT => 'Transferred Out',
            self::TRANSFER_RECEIVED => 'Transferred In',
            self::SOLD => 'Sold',
            self::SALE_RETURNED => 'Sale Returned',
            self::PURCHASE_RETURNED => 'Returned to Supplier',
            self::DAMAGED => 'Marked Damaged',
            self::WASTED => 'Marked Wasted',
            self::EXPIRED => 'Marked Expired',
            self::SENT_FOR_REPAIR => 'Sent for Repair',
            self::RETURNED_FROM_REPAIR => 'Returned from Repair',
            self::REPLACED => 'Replaced',
            self::DECOMMISSIONED => 'Decommissioned',
            self::ADDED_MANUALLY => 'Added Manually',
        ];
    }
}
