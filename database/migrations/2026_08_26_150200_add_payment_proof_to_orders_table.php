<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Website bank-transfer receipt + admin payment confirmation metadata.
 * Payment status itself remains derived from paid_amount vs total.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_proof')) {
                $table->string('payment_proof')->nullable()->after('delivery_address');
            }
            if (!Schema::hasColumn('orders', 'payment_confirmed_at')) {
                $table->timestamp('payment_confirmed_at')->nullable()->after('payment_proof');
            }
            if (!Schema::hasColumn('orders', 'payment_confirmed_by_id')) {
                $table->unsignedBigInteger('payment_confirmed_by_id')->nullable()->after('payment_confirmed_at');
            }
            if (!Schema::hasColumn('orders', 'client_request_id')) {
                $table->string('client_request_id', 64)->nullable()->after('payment_confirmed_by_id');
                $table->unique(['business_id', 'client_request_id'], 'orders_business_client_request_unique');
            }
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'client_request_id')) {
                $table->dropUnique('orders_business_client_request_unique');
                $table->dropColumn('client_request_id');
            }
            foreach (['payment_confirmed_by_id', 'payment_confirmed_at', 'payment_proof'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
