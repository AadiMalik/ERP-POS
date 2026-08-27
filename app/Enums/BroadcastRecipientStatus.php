<?php

namespace App\Enums;

class BroadcastRecipientStatus
{
    const PENDING = 'pending';
    const SENDING = 'sending';
    const SENT = 'sent';
    const FAILED = 'failed';
    const CANCELLED = 'cancelled';

    public static function labels(): array
    {
        return [
            self::PENDING => 'Pending',
            self::SENDING => 'Sending',
            self::SENT => 'Sent',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        ];
    }
}
