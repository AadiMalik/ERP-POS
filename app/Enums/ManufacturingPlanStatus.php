<?php

namespace App\Enums;

class ManufacturingPlanStatus
{
    const DRAFT = 'draft';
    const NOT_COMPLETE = 'not_complete';
    const COMPLETED = 'completed';
    const CANCELLED = 'cancelled';

    public static function all()
    {
        return [self::DRAFT, self::NOT_COMPLETE, self::COMPLETED, self::CANCELLED];
    }

    public static function getOptions()
    {
        return [
            self::DRAFT => 'Draft',
            self::NOT_COMPLETE => 'Not Complete',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Statuses in which a plan can no longer be confirmed/reserved/cancelled
     * via the normal lifecycle (its reservation has already been fully
     * settled one way or another).
     */
    public static function terminal()
    {
        return [self::COMPLETED, self::CANCELLED];
    }
}
