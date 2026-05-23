<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->nullable();
            $table->boolean('enable_whatsapp')->default(false);
            $table->enum('provider', ['meta','twilio','ultramsg','greenapi'])->default('meta');
            $table->text('api_key')->nullable();
            $table->string('access_token')->nullable();
            $table->string('instance_id')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->text('webhook_url')->nullable();
            $table->boolean('send_invoice')->default(true);
            $table->boolean('send_receipt')->default(true);
            
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whatsapp_settings');
    }
};
