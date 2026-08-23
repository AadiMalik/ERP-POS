<?php

use App\Enums\AccountSubTypes;
use App\Enums\AccountTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backfills the stable `code` column on every existing account_types/
 * account_sub_types row (across every business, plus the global template)
 * that doesn't have one yet, so AccountClassifier and the accounting reports
 * can classify by code instead of the renamable `name` column - see the
 * Phase 1 plan's "Account Category stable codes" item and
 * AccountTypes::CODES / AccountSubTypes::CODES.
 *
 * Matches each row's CURRENT `name` against the known default name -> code
 * map. A row whose name no longer matches any default (already renamed by a
 * business before this migration) can't be safely auto-matched - it is left
 * with a null code and logged for manual review, since it was already
 * silently excluded from every report that classifies by name today; this
 * migration does not make that pre-existing situation any worse.
 */
return new class extends Migration
{
    public function up()
    {
        foreach (AccountTypes::CODES as $name => $code) {
            DB::table('account_types')
                ->where('name', $name)
                ->where(fn ($q) => $q->whereNull('code')->orWhere('code', ''))
                ->update(['code' => $code]);
        }

        foreach (AccountSubTypes::CODES as $name => $code) {
            DB::table('account_sub_types')
                ->where('name', $name)
                ->where(fn ($q) => $q->whereNull('code')->orWhere('code', ''))
                ->update(['code' => $code]);
        }

        $unmatchedTypes = DB::table('account_types')
            ->where('is_deleted', 0)
            ->where(fn ($q) => $q->whereNull('code')->orWhere('code', ''))
            ->get(['account_type_id', 'business_id', 'name']);

        $unmatchedSubTypes = DB::table('account_sub_types')
            ->where('is_deleted', 0)
            ->where(fn ($q) => $q->whereNull('code')->orWhere('code', ''))
            ->get(['account_sub_type_id', 'business_id', 'name']);

        foreach ($unmatchedTypes as $row) {
            Log::warning('Account Type has no stable code after backfill - was likely renamed from its default before this migration ran. Assign a code manually via the Account Type edit screen.', (array) $row);
        }

        foreach ($unmatchedSubTypes as $row) {
            Log::warning('Account Sub Type has no stable code after backfill - was likely renamed from its default before this migration ran. Assign a code manually via the Account Sub Type edit screen.', (array) $row);
        }
    }

    public function down()
    {
        // Intentionally a no-op: `code` values restored here are the same
        // stable defaults the app already relies on elsewhere (e.g.
        // AccountTypeService::resetBusinessAccountType() sets the same
        // values), so there is nothing safe to "undo" without risking
        // wiping a code a business has since set deliberately.
    }
};
