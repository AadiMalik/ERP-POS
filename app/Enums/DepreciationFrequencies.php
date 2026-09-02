<?php

namespace App\Enums;

class DepreciationFrequencies
{
    const DAILY = 'daily';
    const WEEKLY = 'weekly';
    const MONTHLY = 'monthly';
    const YEARLY = 'yearly';

    public static function all(): array
    {
        return [
            self::DAILY,
            self::WEEKLY,
            self::MONTHLY,
            self::YEARLY,
        ];
    }

    public static function labels(): array
    {
        return [
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::MONTHLY => 'Monthly',
            self::YEARLY => 'Yearly',
        ];
    }

    /** Periods per year used for straight-line period amount. */
    public static function periodsPerYear(string $frequency): int
    {
        return match ($frequency) {
            self::DAILY => 365,
            self::WEEKLY => 52,
            self::MONTHLY => 12,
            self::YEARLY => 1,
            default => 12,
        };
    }
}
