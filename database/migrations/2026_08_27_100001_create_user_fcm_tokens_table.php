<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Multi-device FCM registration tokens per user (business-scoped).
     * One user may have many devices; inactive tokens are excluded from
     * future broadcast recipient selection.
     */
    public function up()
    {
        Schema::create('user_fcm_tokens', function (Blueprint $table) {
            $table->uuid('user_fcm_token_id')->primary();
            $table->uuid('business_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            // FCM tokens are typically < 200 chars; varchar keeps MySQL unique indexes valid.
            $table->string('fcm_token', 512);
            $table->string('device_id')->nullable();
            $table->string('device_type', 32)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();

            $table->unique(['business_id', 'fcm_token'], 'user_fcm_tokens_business_token_unique');
            $table->index(['business_id', 'user_id', 'is_active'], 'user_fcm_tokens_biz_user_active_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_fcm_tokens');
    }
};
