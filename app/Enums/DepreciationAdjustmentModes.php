<?php

namespace App\Enums;

class DepreciationAdjustmentModes
{
    const NONE = 'none';
    const INCREASE = 'increase';
    const DECREASE = 'decrease';

    public static function all(): array
    {
        return [
            self::NONE,
            self::INCREASE,
            self::DECREASE,
        ];
    }

    public static function labels(): array
    {
        return [
            self::NONE => 'None (constant amount)',
            self::INCREASE => 'Increase over time',
            self::DECREASE => 'Decrease over time',
        ];
    }
}
