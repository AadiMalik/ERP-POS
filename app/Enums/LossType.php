<?php

namespace App\Enums;

/**
 * The 7 loss categories a Waste/Damage/Expiry line can be recorded under.
 * Each maps onto one of the 3 stock-ledger buckets (TransactionType::DAMAGE/
 * EXPIRED/WASTAGE + ReferenceType::DAMAGE_NOTE/EXPIRY_NOTE/WASTAGE_NOTE) that
 * already existed - unused - in the codebase before this module, so the
 * ledger/report side needs no changes. This enum only drives the finer-
 * grained categorisation on waste_damage_expiry_details and the dedicated
 * Waste/Damage/Expiry report.
 */
class LossType
{
    const WASTE = 'waste';
    const DAMAGED = 'damaged';
    const EXPIRED = 'expired';
    const SPOILED = 'spoiled';
    const BROKEN = 'broken';
    const LOST = 'lost';
    const OTHER = 'other';

    public static function getOptions()
    {
        return [
            self::WASTE => 'Waste',
            self::DAMAGED => 'Damaged',
            self::EXPIRED => 'Expired',
            self::SPOILED => 'Spoiled',
            self::BROKEN => 'Broken',
            self::LOST => 'Lost/Missing',
            self::OTHER => 'Other',
        ];
    }

    protected static function map()
    {
        return [
            self::DAMAGED => [TransactionType::DAMAGE, ReferenceType::DAMAGE_NOTE],
            self::BROKEN  => [TransactionType::DAMAGE, ReferenceType::DAMAGE_NOTE],
            self::EXPIRED => [TransactionType::EXPIRED, ReferenceType::EXPIRY_NOTE],
            self::SPOILED => [TransactionType::EXPIRED, ReferenceType::EXPIRY_NOTE],
            self::WASTE   => [TransactionType::WASTAGE, ReferenceType::WASTAGE_NOTE],
            self::LOST    => [TransactionType::WASTAGE, ReferenceType::WASTAGE_NOTE],
            self::OTHER   => [TransactionType::WASTAGE, ReferenceType::WASTAGE_NOTE],
        ];
    }

    public static function toTransactionType($loss_type)
    {
        return self::map()[$loss_type][0] ?? TransactionType::WASTAGE;
    }

    public static function toReferenceType($loss_type)
    {
        return self::map()[$loss_type][1] ?? ReferenceType::WASTAGE_NOTE;
    }
}
