<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-business payment gateway configuration (Website/Mobile App only - see
 * resources/docs/developer for the framework overview). One row per business
 * per provider. config_sandbox/config_live are independent encrypted JSON
 * blobs (see App\Models\PaymentGateway's 'encrypted:array' casts) so
 * switching active_mode never loses the other mode's credentials.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->uuid('payment_gateway_id')->primary();
            $table->string('business_id');
            $table->string('provider_code');
            $table->string('display_name');
            $table->string('description', 500)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('country', 100)->nullable();
            $table->boolean('is_active')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('website_enabled')->default(true);
            $table->boolean('mobile_enabled')->default(true);
            $table->json('supported_currencies')->nullable();
            $table->json('supported_payment_methods')->nullable();
            $table->enum('active_mode', ['sandbox', 'live'])->default('sandbox');
            $table->text('config_sandbox')->nullable();
            $table->text('config_live')->nullable();
            // Linked payment_methods row (type=gateway) auto-managed by
            // PaymentGatewayService so order_payments/accounting reuse the
            // existing tender pipeline unchanged.
            $table->string('payment_method_id')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->unique(['business_id', 'provider_code']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_gateways');
    }
};
