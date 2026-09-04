<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stock reservation primitive for Manufacturing Plans (and any future
 * reserve-ahead-of-consumption workflow). Available/free stock everywhere in
 * the app is `quantity - reserved_quantity`; reservation itself never writes
 * a product_variation_stock_transactions row (no movement happened yet).
 * Defaults to 0 for every existing row, so businesses that never enable
 * Manufacturing see byte-for-byte identical behavior to today.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('product_variation_stocks', function (Blueprint $table) {
            $table->decimal('reserved_quantity', 18, 4)->default(0.0000)->after('quantity');
        });
    }

    public function down()
    {
        Schema::table('product_variation_stocks', function (Blueprint $table) {
            $table->dropColumn('reserved_quantity');
        });
    }
};
