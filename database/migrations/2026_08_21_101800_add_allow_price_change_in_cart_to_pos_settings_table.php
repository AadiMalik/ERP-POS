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
            // Master switch for manual price editing in the POS cart. When
            // off, no cashier - regardless of the order.price.change
            // permission - can edit a line's price. When on, the existing
            // permission governs who actually sees the editable price field.
            $table->boolean('allow_price_change_in_cart')->default(false)->after('allow_mixed_sale_types');
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
            $table->dropColumn('allow_price_change_in_cart');
        });
    }
};
