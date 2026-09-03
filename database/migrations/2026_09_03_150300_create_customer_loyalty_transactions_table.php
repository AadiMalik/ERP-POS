<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only ledger backing customer_profiles.loyalty_points (available) and
 * loyalty_points_reserved - mirrors the aggregate+ledger pattern already used
 * for store credit (customer_store_credit_transactions). One row per
 * balance-changing event:
 *   earned    (+available, from a paid/completed qualifying order)
 *   reserved  (available -> reserved, points selected for redemption at
 *              order save time, before the order is paid)
 *   released  (reserved -> available, the order was cancelled/voided before
 *              consumption, or a draft's reservation was resized)
 *   consumed  (reserved -> gone, the order was paid/completed)
 *   reversed  (gone -> available, a consumed order was later voided)
 *   adjusted  (+/- available, manual admin correction or an earned-points
 *              take-back on void/return)
 *   expired   (reserved for a future points-expiry feature; unused today)
 * Never deleted - corrections are new rows, never edits to old ones.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('customer_loyalty_transactions', function (Blueprint $table) {
            $table->uuid('customer_loyalty_transaction_id')->primary();
            $table->string('business_id');
            $table->unsignedBigInteger('customer_id');

            $table->enum('transaction_type', ['earned', 'reserved', 'released', 'consumed', 'reversed', 'adjusted', 'expired']);
            $table->decimal('points', 18, 3);
            $table->decimal('monetary_value', 18, 3)->nullable();

            $table->decimal('available_balance_after', 18, 3);
            $table->decimal('reserved_balance_after', 18, 3);

            // 'order' | 'order_return' - reference_id is the order_id/order_return_id.
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();

            $table->text('description')->nullable();

            $table->integer('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->index(['business_id', 'customer_id']);
            $table->index(['reference_type', 'reference_id'], 'clt_reference_index');
            $table->foreign('customer_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_loyalty_transactions');
    }
};
