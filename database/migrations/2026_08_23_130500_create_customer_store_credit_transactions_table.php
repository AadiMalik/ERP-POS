<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only ledger backing customer_profiles.store_credit_balance (the
 * aggregate) - mirrors the aggregate+ledger pattern already used for stock
 * (ProductVariationStock + ProductVariationStockTransaction). One row per
 * balance-changing event: issued (+, from an approved return with no
 * refund method), redeemed (-, spent as a POS payment), reversed (+, a
 * redeeming order was voided, giving the spent amount back), revoked (-,
 * an issuing return was itself un-approved/reversed, taking the credit
 * back) - issued/reversed both add to the balance and revoked/redeemed
 * both subtract, but are kept as distinct types since they represent
 * opposite-cause events an admin reading the ledger needs to tell apart.
 * Phase 2 plan, batch E.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('customer_store_credit_transactions', function (Blueprint $table) {
            $table->uuid('customer_store_credit_transaction_id')->primary();
            $table->string('business_id');
            $table->unsignedBigInteger('customer_id');

            $table->enum('transaction_type', ['issued', 'redeemed', 'reversed', 'revoked']);
            $table->decimal('amount', 18, 3);
            $table->decimal('balance_after', 18, 3);

            // 'order_return' (issued) | 'order' (redeemed/reversed)
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();

            $table->text('description')->nullable();

            $table->integer('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->index(['business_id', 'customer_id']);
            $table->index(['reference_type', 'reference_id'], 'csct_reference_index');
            $table->foreign('customer_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_store_credit_transactions');
    }
};
