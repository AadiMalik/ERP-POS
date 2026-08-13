<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->decimal('overall_tax_rate', 5, 2)->default(0)->after('timezone');
            $table->decimal('card_tax_rate', 5, 2)->default(0)->after('overall_tax_rate');
        });

        // Carry forward whatever rate businesses had already configured in the
        // old (dead) tax_rate field, so nothing silently resets to 0.
        DB::table('business_settings')->update([
            'overall_tax_rate' => DB::raw('tax_rate'),
        ]);

        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn(['tax_type', 'tax_rate']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->enum('tax_type', ['inclusive', 'exclusive'])->default('exclusive')->after('timezone');
            $table->decimal('tax_rate', 8, 2)->default(18.00)->after('tax_type');
        });

        DB::table('business_settings')->update([
            'tax_rate' => DB::raw('overall_tax_rate'),
        ]);

        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn(['overall_tax_rate', 'card_tax_rate']);
        });
    }
};
