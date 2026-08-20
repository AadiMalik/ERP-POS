<?php

namespace App\Services\ImportExport\Support;

use App\Enums\RoleNames;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The current business/branch/user scope an import or export runs under -
 * mirrors the `$request->business_id ?? Auth::user()->business_id` pattern
 * already used by every module's store() method.
 */
class ImportContext
{
    public function __construct(
        public string $moduleKey,
        public ?string $businessId,
        public ?string $branchId,
        public $userId,
        public bool $isSuperAdmin = false,
    ) {
    }

    public static function fromRequest(Request $request, string $moduleKey): self
    {
        $user = Auth::user();

        return new self(
            moduleKey: $moduleKey,
            businessId: $request->business_id ?? $user?->business_id,
            branchId: $request->branch_id ?? $user?->branch_id,
            userId: Auth::id(),
            isSuperAdmin: getRoleName() === RoleNames::SUPERADMIN,
        );
    }
}
