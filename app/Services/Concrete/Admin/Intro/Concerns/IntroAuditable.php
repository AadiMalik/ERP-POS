<?php

namespace App\Services\Concrete\Admin\Intro\Concerns;

use Illuminate\Support\Facades\Auth;

trait IntroAuditable
{
    protected function createAudit(array $obj): array
    {
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();
        return $obj;
    }

    protected function updateAudit(array $obj): array
    {
        $obj['updatedby_id'] = Auth::id();
        $obj['date_updated'] = now();
        return $obj;
    }

    protected function deleteAudit(): array
    {
        return [
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ];
    }
}
