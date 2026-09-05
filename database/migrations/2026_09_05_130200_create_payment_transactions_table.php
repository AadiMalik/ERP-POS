<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Full payment-gateway lifecycle record for a Website/Mobile App order. One
 * row is created at "initiate" time (this also serves as the payment
 * "session" - website/mobile checkout is and stays single-payment-per-order,
 * matching the existing architecture's limitation). Never stores card
 * numbers/CVV or other prohibited card data - `meta` only ever holds safe,
 * non-secret reference fields.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->uuid('payment_transaction_id')->primary();
            $table->string('business_id');
            $table->string('order_id');
            $table->integer('user_id')->nullable();
            $table->string('payment_gateway_id');
            $table->string('provider_code');
            $table->enum('environment', ['sandbox', 'live']);
            $table->string('payment_method_code')->nullable();
            $table->enum('client_platform', ['website', 'mobile']);
            $table->string('internal_reference')->unique();
            $table->string('gateway_reference')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10);
            $table->enum('status', [
                'initiated', 'pending', 'processing', 'authorized', 'paid',
                'failed', 'cancelled', 'expired', 'refunded', 'partially_refunded', 'disputed', 'unknown',
            ])->default('initiated');
            $table->string('failure_code')->nullable();
            $table->string('failure_reason', 500)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_method')->nullable();
            $table->string('refund_of_transaction_id')->nullable();
            $table->decimal('refunded_amount', 15, 2)->default(0);
            $table->json('meta')->nullable();

            $table->integer('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();

            $table->index('order_id');
            $table->index(['provider_code', 'gateway_transaction_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_transactions');
    }
};
