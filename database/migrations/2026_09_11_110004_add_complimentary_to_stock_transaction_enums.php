<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Widen product_variation_stock_transactions ENUM columns to accept
 * TransactionType::COMPLIMENTARY / COMPLIMENTARY_RETURN and
 * ReferenceType::COMPLIMENTARY / COMPLIMENTARY_RETURN.
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
            'cost_price_adjustment',
            'complimentary',
            'complimentary_return'
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
            'cost_price_adjustment',
            'complimentary',
            'complimentary_return'
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
};
