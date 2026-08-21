<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('pos_settings', function (Blueprint $table) {
            // Whether a cashier may set a different Sale Type per cart line
            // instead of every line inheriting the order-level Sale Type.
            $table->boolean('allow_mixed_sale_types')->default(true)->after('discount_level');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->dropColumn('allow_mixed_sale_types');
        });
    }
};
