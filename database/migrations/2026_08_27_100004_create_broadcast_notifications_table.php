<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('broadcast_notifications', function (Blueprint $table) {
            $table->uuid('broadcast_notification_id')->primary();
            $table->uuid('business_id')->index();
            $table->uuid('template_id')->nullable()->index();
            $table->string('title');
            $table->text('body');
            $table->string('image')->nullable();
            $table->json('data')->nullable();
            $table->enum('status', [
                'draft',
                'queued',
                'processing',
                'completed',
                'cancelled',
                'failed',
            ])->default('draft')->index();
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('pending_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('cancelled_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->boolean('is_deleted')->default(false);
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'status', 'is_deleted'], 'broadcast_notifications_biz_status_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('broadcast_notifications');
    }
};
