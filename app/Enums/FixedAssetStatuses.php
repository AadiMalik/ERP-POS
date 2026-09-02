<?php

namespace App\Enums;

/**
 * Lifecycle / depreciation status for accounting Fixed Assets.
 * Distinct from HRM Asset statuses (available/allocated/…).
 */
class FixedAssetStatuses
{
    const ACTIVE = 'active';
    const PAUSED = 'paused';
    const FULLY_DEPRECIATED = 'fully_depreciated';
    const SOLD = 'sold';
    const DISPOSED = 'disposed';
    const WRITTEN_OFF = 'written_off';
    const DAMAGED = 'damaged';

    public static function all(): array
    {
        return [
            self::ACTIVE,
            self::PAUSED,
            self::FULLY_DEPRECIATED,
            self::SOLD,
            self::DISPOSED,
            self::WRITTEN_OFF,
            self::DAMAGED,
        ];
    }

    public static function depreciable(): array
    {
        return [self::ACTIVE];
    }

    public static function terminal(): array
    {
        return [
            self::SOLD,
            self::DISPOSED,
            self::WRITTEN_OFF,
            self::DAMAGED,
        ];
    }

    public static function labels(): array
    {
        return [
            self::ACTIVE => 'Active',
            self::PAUSED => 'Paused / Stopped',
            self::FULLY_DEPRECIATED => 'Fully Depreciated',
            self::SOLD => 'Sold',
            self::DISPOSED => 'Disposed',
            self::WRITTEN_OFF => 'Written Off',
            self::DAMAGED => 'Damaged / Written Off',
        ];
    }
}
