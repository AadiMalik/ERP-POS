<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mirrors 2026_09_04_140300_seed_stock_loss_journal.php. Used by
 * CostPriceAdjustmentService::applyPosting() to post the Dr Inventory / Cr
 * Inventory Cost Adjustment entry (or reversed, for a decrease) when an
 * approved Cost Price Adjustment has a non-zero value.
 */
return new class extends Migration
{
    public function up()
    {
        $exists = DB::table('journals')
            ->where('short', 'ICJ')
            ->where('is_deleted', 0)
            ->exists();

        if (!$exists) {
            DB::table('journals')->insert([
                'journal_id'   => (string) Str::uuid(),
                'name'         => 'Inventory Cost Adjustment Voucher',
                'short'        => 'ICJ',
                'is_deleted'   => 0,
                'date_created' => now(),
            ]);
        }
    }

    public function down()
    {
        DB::table('journals')->where('short', 'ICJ')->delete();
    }
};
