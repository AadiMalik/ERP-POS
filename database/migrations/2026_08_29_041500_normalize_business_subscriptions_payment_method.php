<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * business_subscriptions.payment_method used 'bank transfer' (space) while the
 * rest of the app (subscription_payments, intro registration, My Subscription)
 * uses 'bank_transfer'. Align the enum and normalize existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('business_subscriptions')
            ->where('payment_method', 'bank transfer')
            ->update(['payment_method' => 'cash']);

        // Expand enum to accept both temporarily, then rewrite values, then lock.
        DB::statement("ALTER TABLE business_subscriptions MODIFY payment_method ENUM('cash', 'bank transfer', 'bank_transfer', 'cheque', 'online') DEFAULT 'cash'");

        DB::table('business_subscriptions')
            ->where('payment_method', 'bank transfer')
            ->update(['payment_method' => 'bank_transfer']);

        DB::statement("ALTER TABLE business_subscriptions MODIFY payment_method ENUM('cash', 'bank_transfer', 'cheque', 'online') DEFAULT 'cash'");
    }

    public function down(): void
    {
        DB::table('business_subscriptions')
            ->where('payment_method', 'online')
            ->update(['payment_method' => 'cash']);

        DB::statement("ALTER TABLE business_subscriptions MODIFY payment_method ENUM('cash', 'bank transfer', 'bank_transfer', 'cheque') DEFAULT 'cash'");

        DB::table('business_subscriptions')
            ->where('payment_method', 'bank_transfer')
            ->update(['payment_method' => 'bank transfer']);

        DB::statement("ALTER TABLE business_subscriptions MODIFY payment_method ENUM('cash', 'bank transfer', 'cheque') DEFAULT 'cash'");
    }
};
