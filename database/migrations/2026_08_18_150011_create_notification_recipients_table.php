<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user read state for a notification. Split from `notifications`
     * because a single event can have multiple recipients (e.g. Business
     * Admin + Super Admin for subscription expiry) that must each get their
     * own unread badge/read state.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->uuid('notification_recipient_id')->primary();
            $table->uuid('notification_id');

            // Matches users.id (auto-increment), not the UUID PKs used by
            // business-data tables.
            $table->integer('user_id');

            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->foreign('notification_id')->references('notification_id')->on('notifications')->onDelete('cascade');
            $table->unique(['notification_id', 'user_id']);
            $table->index(['user_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_recipients');
    }
};
