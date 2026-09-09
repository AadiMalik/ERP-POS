<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds one branch_tax_settings row per existing branch from that branch's
 * business's current business_settings rates, always exclusive (the only
 * mode that has ever existed) - so no existing business's tax behavior
 * changes until an admin explicitly reconfigures a branch on the new Tax
 * settings tab.
 */
return new class extends Migration
{
    public function up()
    {
        DB::table('branches')
            ->leftJoin('business_settings', 'business_settings.business_id', '=', 'branches.business_id')
            ->where('branches.is_deleted', 0)
            ->select('branches.branch_id', 'branches.business_id', 'business_settings.overall_tax_rate', 'business_settings.card_tax_rate')
            ->orderBy('branches.branch_id')
            ->get()
            ->each(function ($row) {
                DB::table('branch_tax_settings')->updateOrInsert(
                    ['branch_id' => $row->branch_id],
                    [
                        'business_id' => $row->business_id,
                        'overall_tax_rate' => $row->overall_tax_rate ?? 0,
                        'card_tax_rate' => $row->card_tax_rate ?? 0,
                        'tax_type' => 'exclusive',
                        'date_created' => now(),
                    ]
                );
            });
    }

    public function down()
    {
        DB::table('branch_tax_settings')->truncate();
    }
};
