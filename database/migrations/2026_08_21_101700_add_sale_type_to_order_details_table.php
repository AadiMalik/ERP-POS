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
        Schema::table('order_details', function (Blueprint $table) {
            $table->string('sale_type_id')->nullable()->after('unit_price');

            // Net per-unit price after the variation discount - the one value
            // not already derivable from another stored per-unit column
            // (unit_price is pre-discount, subtotal/total are quantity-scaled).
            $table->decimal('final_unit_price', 18, 3)->default(0)->after('sale_type_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn(['sale_type_id', 'final_unit_price']);
        });
    }
};
