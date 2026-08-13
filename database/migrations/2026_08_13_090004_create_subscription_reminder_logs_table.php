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
        Schema::create('subscription_reminder_logs', function (Blueprint $table) {
            $table->uuid('subscription_reminder_log_id')->primary();
            $table->string('business_id')->nullable();
            $table->string('business_subscription_id')->nullable();
            $table->integer('threshold_days');
            $table->enum('channel', ['email', 'sms', 'whatsapp']);
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->timestamp('sent_at')->nullable();

            $table->integer('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->unique(['business_subscription_id', 'threshold_days', 'channel'], 'sub_reminder_dedupe_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_reminder_logs');
    }
};
