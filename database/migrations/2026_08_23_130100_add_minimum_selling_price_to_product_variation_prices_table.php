<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sale-type-specific minimum selling price override, alongside the price
 * column this table already keys by (product_variation_id, sale_type_id).
 * Null = no sale-type-specific floor configured for that pair; the product
 * variation's own flat minimum_selling_price stays the fallback - see
 * VariationPricingService::resolveBulk(). Phase 2 plan, batch A3.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('product_variation_prices', function (Blueprint $table) {
            $table->decimal('minimum_selling_price', 18, 4)->nullable()->after('price');
        });
    }

    public function down()
    {
        Schema::table('product_variation_prices', function (Blueprint $table) {
            $table->dropColumn('minimum_selling_price');
        });
    }
};
