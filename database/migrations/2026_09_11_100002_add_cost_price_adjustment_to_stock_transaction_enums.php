<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * product_variation_stock_transactions.transaction_type / reference_type are
 * DB-level ENUMs (see 2026_06_24_142338_create_product_variation_stock_transactions_table.php).
 * Widen both to accept TransactionType::COST_ADJUSTMENT / ReferenceType::COST_PRICE_ADJUSTMENT
 * ('cost_price_adjustment'), used by CostPriceAdjustmentService::applyPosting()
 * to record a valuation-only entry (base_quantity = 0, never a stock movement).
 */
return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE product_variation_stock_transactions MODIFY transaction_type ENUM(
            'opening',
            'purchase',
            'purchase_return',
            'sale',
            'sale_return',
            'stock_take_increase',
            'stock_take_decrease',
            'adjustment',
            'damage',
            'expired',
            'wastage',
            'consumption',
            'gift',
            'sample',
            'transfer_in',
            'transfer_out',
            'production_in',
            'production_out',
            'cost_price_adjustment'
        ) NULL");

        DB::statement("ALTER TABLE product_variation_stock_transactions MODIFY reference_type ENUM(
            'opening_stock',
            'purchase',
            'grn',
            'purchase_return',
            'sale',
            'sale_return',
            'stock_taking',
            'damage_note',
            'expiry_note',
            'wastage_note',
            'stock_transfer',
            'production',
            'consumption',
            'gift',
            'sample',
            'manual',
            'cost_price_adjustment'
        ) NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE product_variation_stock_transactions MODIFY transaction_type ENUM(
            'opening',
            'purchase',
            'purchase_return',
            'sale',
            'sale_return',
            'stock_take_increase',
            'stock_take_decrease',
            'adjustment',
            'damage',
            'expired',
            'wastage',
            'consumption',
            'gift',
            'sample',
            'transfer_in',
            'transfer_out',
            'production_in',
            'production_out'
        ) NULL");

        DB::statement("ALTER TABLE product_variation_stock_transactions MODIFY reference_type ENUM(
            'opening_stock',
            'purchase',
            'grn',
            'purchase_return',
            'sale',
            'sale_return',
            'stock_taking',
            'damage_note',
            'expiry_note',
            'wastage_note',
            'stock_transfer',
            'production',
            'consumption',
            'gift',
            'sample',
            'manual'
        ) NULL");
    }
};
