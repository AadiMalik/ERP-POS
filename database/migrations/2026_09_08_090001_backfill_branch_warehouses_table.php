<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Preserve today's behavior under the new pivot: a warehouse that already
     * belongs to one branch gets exactly that link; a warehouse with a null
     * branch_id (the old "shared across every branch of the business"
     * convention - see ValidatesWarehouse::assertValidWarehouse()) gets
     * linked to every branch of its business, so no branch loses stock it
     * could already sell.
     *
     * @return void
     */
    public function up()
    {
        $now = now();

        DB::table('warehouses')
            ->whereNotNull('branch_id')
            ->where('is_deleted', 0)
            ->orderBy('date_created')
            ->select('warehouse_id', 'branch_id')
            ->chunkById(200, function ($warehouses) use ($now) {
                $rows = $warehouses->map(fn ($w) => [
                    'branch_id' => $w->branch_id,
                    'warehouse_id' => $w->warehouse_id,
                    'priority' => 0,
                    'date_created' => $now,
                ])->all();

                if (!empty($rows)) {
                    DB::table('branch_warehouses')->insertOrIgnore($rows);
                }
            }, 'warehouse_id');

        DB::table('warehouses')
            ->whereNull('branch_id')
            ->where('is_deleted', 0)
            ->select('warehouse_id', 'business_id')
            ->orderBy('warehouse_id')
            ->chunkById(200, function ($warehouses) use ($now) {
                foreach ($warehouses as $warehouse) {
                    $branch_ids = DB::table('branches')
                        ->where('business_id', $warehouse->business_id)
                        ->where('is_deleted', 0)
                        ->pluck('branch_id');

                    $rows = $branch_ids->map(fn ($branch_id) => [
                        'branch_id' => $branch_id,
                        'warehouse_id' => $warehouse->warehouse_id,
                        'priority' => 0,
                        'date_created' => $now,
                    ])->all();

                    if (!empty($rows)) {
                        DB::table('branch_warehouses')->insertOrIgnore($rows);
                    }
                }
            }, 'warehouse_id');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('branch_warehouses')->truncate();
    }
};
