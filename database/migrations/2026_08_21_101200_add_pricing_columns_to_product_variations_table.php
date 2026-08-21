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
        Schema::table('product_variations', function (Blueprint $table) {
            // Hard floor - the final price after the variation discount cannot
            // fall below this without the order.price.override-minimum permission.
            $table->decimal('minimum_selling_price', 18, 4)->nullable()->after('sale_price');

            // Auto-applied at POS add-to-cart time for whichever sale types are
            // selected in discount_apply_all / product_variation_discount_sale_types.
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('minimum_selling_price');

            // true = discount applies to every active Sale Type ("All Sale
            // Types"); false = only to the sale types in the pivot table.
            $table->boolean('discount_apply_all')->default(true)->after('discount_percentage');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn(['minimum_selling_price', 'discount_percentage', 'discount_apply_all']);
        });
    }
};
