<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mirrors 2026_09_04_090700_seed_manufacturing_journals.php. Used by
 * WasteDamageExpiryService::applyPosting() to post the Dr Stock Adjustment /
 * Cr Inventory entry when an approved Waste/Damage/Expiry write-off has a
 * non-zero value.
 */
return new class extends Migration
{
    public function up()
    {
        $exists = DB::table('journals')
            ->where('short', 'SLV')
            ->where('is_deleted', 0)
            ->exists();

        if (!$exists) {
            DB::table('journals')->insert([
                'journal_id'   => (string) Str::uuid(),
                'name'         => 'Stock Loss Voucher',
                'short'        => 'SLV',
                'is_deleted'   => 0,
                'date_created' => now(),
            ]);
        }
    }

    public function down()
    {
        DB::table('journals')->where('short', 'SLV')->delete();
    }
};
