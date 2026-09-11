<?php

namespace App\Enums;

class ComplimentaryStatus
{
    const NONE = 'none';
    const PARTIAL = 'partial';
    const FULL = 'full';

    public static function getOptions(): array
    {
        return [
            self::NONE => __('complimentary.status_none'),
            self::PARTIAL => __('complimentary.status_partial'),
            self::FULL => __('complimentary.status_full'),
        ];
    }

    public static function isComplimentary(?string $status): bool
    {
        return in_array($status, [self::PARTIAL, self::FULL], true);
    }

    public static function fromLineFlags(int $complimentary_count, int $total_count): string
    {
        if ($total_count <= 0 || $complimentary_count <= 0) {
            return self::NONE;
        }

        if ($complimentary_count >= $total_count) {
            return self::FULL;
        }

        return self::PARTIAL;
    }
}
