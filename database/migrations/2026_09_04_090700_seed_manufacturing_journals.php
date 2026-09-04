<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mirrors 2026_09_02_221520_seed_fixed_asset_journals.php. Only labor/
 * overhead/other production cost postings and abnormal-wastage postings use
 * a journal (see ManufacturingAccountingService) - material-only production
 * moves value within the same inventory control account and posts nothing.
 */
return new class extends Migration
{
    public function up()
    {
        $journals = [
            'PCV' => 'Production Cost Voucher',
            'PWV' => 'Production Wastage Voucher',
        ];

        foreach ($journals as $short => $name) {
            $exists = DB::table('journals')
                ->where('short', $short)
                ->where('is_deleted', 0)
                ->exists();

            if (!$exists) {
                DB::table('journals')->insert([
                    'journal_id'   => (string) Str::uuid(),
                    'name'         => $name,
                    'short'        => $short,
                    'is_deleted'   => 0,
                    'date_created' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        DB::table('journals')->whereIn('short', ['PCV', 'PWV'])->delete();
    }
};
