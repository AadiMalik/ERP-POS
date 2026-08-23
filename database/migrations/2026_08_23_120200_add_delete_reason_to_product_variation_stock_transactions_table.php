<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records why a stock transaction was reversed (distinct from `remarks`,
 * which describes why the transaction was originally created) - required
 * whenever a transaction is deleted via ProductVariationStockTransactionService::delete(),
 * so the reversal always carries an audit trail. See Phase 1 plan's "Stock
 * Movement Deletion/Reversal fix".
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('product_variation_stock_transactions', function (Blueprint $table) {
            $table->text('delete_reason')->nullable()->after('remarks');
        });
    }

    public function down()
    {
        Schema::table('product_variation_stock_transactions', function (Blueprint $table) {
            $table->dropColumn('delete_reason');
        });
    }
};
