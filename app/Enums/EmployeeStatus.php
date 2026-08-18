<?php

namespace App\Enums;

class EmployeeStatus
{
    const ACTIVE = 'active';
    const ON_LEAVE = 'on_leave';
    const SUSPENDED = 'suspended';
    const RESIGNED = 'resigned';
    const TERMINATED = 'terminated';

    /**
     * Statuses settable manually from the Employees screen. resigned/terminated
     * are exclusively set by the Employee Exit workflow (finalize()) so the two
     * code paths never fight over the same field.
     */
    public static function manuallySettable(): array
    {
        return [self::ACTIVE, self::ON_LEAVE, self::SUSPENDED];
    }
}
