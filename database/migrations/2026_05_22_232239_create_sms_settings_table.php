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
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->nullable();
            $table->boolean('enable_sms')->default(false);
            $table->enum('provider', ['twilio','jazz','brandsms','msg91'])->default('twilio');
            $table->text('api_key')->nullable();
            $table->string('sender_id')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->boolean('send_invoice_sms')->default(false);
            $table->boolean('send_due_sms')->default(false);
            
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
        Schema::dropIfExists('sms_settings');
    }
};
