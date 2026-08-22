<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        $journals = [
            'SPV'  => 'Service Purchase Voucher',
            'SPRV' => 'Service Purchase Return Voucher',
            'SSV'  => 'Service Sale Voucher',
            'SSRV' => 'Service Sale Return Voucher',
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
        DB::table('journals')->whereIn('short', ['SPV', 'SPRV', 'SSV', 'SSRV'])->delete();
    }
};
