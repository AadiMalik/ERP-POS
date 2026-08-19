<?php

namespace App\Enums;

class RecurringFrequency
{
    const DAILY = 'daily';
    const WEEKLY = 'weekly';
    const MONTHLY = 'monthly';
    const YEARLY = 'yearly';

    public static function all(): array
    {
        return [self::DAILY, self::WEEKLY, self::MONTHLY, self::YEARLY];
    }
}
