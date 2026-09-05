<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replay/duplicate-event protection ledger for gateway webhooks, keyed on
 * (provider_code, event_id) independent of whether the event maps to a known
 * transaction yet - the exact same event id can never be processed twice.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_gateway_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider_code');
            $table->string('business_id')->nullable();
            $table->string('event_id');
            $table->string('payload_hash')->nullable();
            $table->enum('status', ['processed', 'ignored', 'invalid'])->default('processed');
            $table->timestamp('received_at');

            $table->unique(['provider_code', 'event_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_gateway_webhook_logs');
    }
};
