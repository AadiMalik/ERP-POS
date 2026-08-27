<?php

namespace App\Enums;

class BroadcastNotificationStatus
{
    const DRAFT = 'draft';
    const QUEUED = 'queued';
    const PROCESSING = 'processing';
    const COMPLETED = 'completed';
    const CANCELLED = 'cancelled';
    const FAILED = 'failed';

    public static function startable(): array
    {
        return [self::DRAFT, self::FAILED];
    }

    public static function cancellable(): array
    {
        return [self::DRAFT, self::QUEUED, self::PROCESSING];
    }

    public static function labels(): array
    {
        return [
            self::DRAFT => 'Draft',
            self::QUEUED => 'Queued',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::FAILED => 'Failed',
        ];
    }
}
