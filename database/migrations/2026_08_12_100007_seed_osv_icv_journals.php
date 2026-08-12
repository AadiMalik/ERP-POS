<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $journals = [
            'OSV' => 'Opening Stock Voucher',
            'ICV' => 'Inventory Count Voucher',
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('journals')->whereIn('short', ['OSV', 'ICV'])->delete();
    }
};
