<?php

namespace App\Enums;

class FixedAssetTransactionTypes
{
    const PURCHASE = 'purchase';
    const ALLOCATION = 'allocation';
    const TRANSFER = 'transfer';
    const DEPRECIATION = 'depreciation';
    const PAUSE = 'pause';
    const RESUME = 'resume';
    const ADJUSTMENT = 'adjustment';
    const SALE = 'sale';
    const DISPOSAL = 'disposal';
    const WASTE = 'waste';
    const DAMAGE = 'damage';
    const WRITE_OFF = 'write_off';

    public static function all(): array
    {
        return [
            self::PURCHASE,
            self::ALLOCATION,
            self::TRANSFER,
            self::DEPRECIATION,
            self::PAUSE,
            self::RESUME,
            self::ADJUSTMENT,
            self::SALE,
            self::DISPOSAL,
            self::WASTE,
            self::DAMAGE,
            self::WRITE_OFF,
        ];
    }

    public static function labels(): array
    {
        return [
            self::PURCHASE => 'Purchase / Acquisition',
            self::ALLOCATION => 'Allocation / Location',
            self::TRANSFER => 'Branch / Location Transfer',
            self::DEPRECIATION => 'Depreciation',
            self::PAUSE => 'Pause Depreciation',
            self::RESUME => 'Resume Depreciation',
            self::ADJUSTMENT => 'Adjustment',
            self::SALE => 'Sale',
            self::DISPOSAL => 'Disposal',
            self::WASTE => 'Waste',
            self::DAMAGE => 'Damage',
            self::WRITE_OFF => 'Write-off',
        ];
    }
}
