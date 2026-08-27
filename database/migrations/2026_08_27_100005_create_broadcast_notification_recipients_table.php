<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('broadcast_notification_recipients', function (Blueprint $table) {
            $table->uuid('broadcast_notification_recipient_id')->primary();
            $table->uuid('broadcast_notification_id');
            $table->unsignedBigInteger('user_id');
            $table->uuid('user_fcm_token_id')->nullable();
            $table->string('fcm_token', 512);
            $table->enum('status', [
                'pending',
                'sending',
                'sent',
                'failed',
                'cancelled',
            ])->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->json('response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();

            $table->index('broadcast_notification_id', 'bnr_campaign_idx');
            $table->index('user_id', 'bnr_user_idx');
            $table->index('user_fcm_token_id', 'bnr_token_id_idx');
            $table->index('status', 'bnr_status_idx');
            $table->index(
                ['broadcast_notification_id', 'status'],
                'bnr_campaign_status_idx'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('broadcast_notification_recipients');
    }
};
