<?php

namespace App\Enums;

class ProductionStatus
{
    const DRAFT = 'draft';
    const COMPLETED = 'completed';
    const CANCELLED = 'cancelled';

    public static function all()
    {
        return [self::DRAFT, self::COMPLETED, self::CANCELLED];
    }

    public static function getOptions()
    {
        return [
            self::DRAFT => 'Draft',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        ];
    }

    public static function terminal()
    {
        return [self::COMPLETED, self::CANCELLED];
    }
}
