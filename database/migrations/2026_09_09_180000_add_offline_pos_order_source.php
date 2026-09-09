<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Order Types/Sources became global master data in the previous
     * migration, so a table that already has rows never re-runs
     * AbstractLookupTypeService::seedDefaults() - it only seeds an empty
     * table. This adds the new "Offline POS" default (OrderSourceService::
     * defaultRows()) retroactively, since the desktop/offline POS client
     * (OfflinePushService::resolvePosOrderSourceId()) is now hardcoded to
     * look it up by code and must find a real row.
     *
     * @return void
     */
    public function up()
    {
        if (DB::table('order_sources')->where('code', 'OFFLINE_POS')->exists()) {
            return;
        }

        $next_sort_order = (int) DB::table('order_sources')->max('sort_order') + 1;

        DB::table('order_sources')->insert([
            'order_source_id' => generateUuid(),
            'name' => 'Offline POS',
            'code' => 'OFFLINE_POS',
            'is_default' => false,
            'status' => 'active',
            'sort_order' => $next_sort_order,
            'is_deleted' => false,
            'date_created' => now(),
        ]);
    }

    /**
     * @return void
     */
    public function down()
    {
        DB::table('order_sources')->where('code', 'OFFLINE_POS')->delete();
    }
};
