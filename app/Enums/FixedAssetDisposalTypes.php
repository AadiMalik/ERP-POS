<?php

namespace App\Enums;

class FixedAssetDisposalTypes
{
    const SALE = 'sale';
    const WASTE = 'waste';
    const DAMAGE = 'damage';
    const THEFT = 'theft';
    const WRITE_OFF = 'write_off';
    const OTHER = 'other';

    public static function all(): array
    {
        return [
            self::SALE,
            self::WASTE,
            self::DAMAGE,
            self::THEFT,
            self::WRITE_OFF,
            self::OTHER,
        ];
    }

    public static function labels(): array
    {
        return [
            self::SALE => 'Sale',
            self::WASTE => 'Waste',
            self::DAMAGE => 'Damage',
            self::THEFT => 'Theft',
            self::WRITE_OFF => 'Write-off',
            self::OTHER => 'Other',
        ];
    }

    public static function requiresSalePrice(string $type): bool
    {
        return $type === self::SALE;
    }
}
