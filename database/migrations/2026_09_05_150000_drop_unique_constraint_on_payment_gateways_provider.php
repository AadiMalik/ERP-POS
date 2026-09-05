<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A business can now keep more than one payment_gateways row per provider
 * (e.g. an old, deactivated Stripe config kept alongside a new one) - only
 * one row per (business_id, provider_code) may ever be `is_active = 1` at a
 * time, enforced in PaymentGatewayService (save() blocks creating a new row
 * while one is active; status() blocks reactivating one while another is
 * active) rather than at the DB level, since the DB can't express "unique
 * among active rows only" here. Replaces the original hard unique
 * constraint with a plain index for lookup performance.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropUnique('payment_gateways_business_id_provider_code_unique');
            $table->index(['business_id', 'provider_code']);
        });
    }

    public function down()
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'provider_code']);
            $table->unique(['business_id', 'provider_code']);
        });
    }
};
