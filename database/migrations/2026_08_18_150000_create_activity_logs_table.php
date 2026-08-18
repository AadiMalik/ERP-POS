<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generic, append-only CRUD/audit trail shared by every module that opts
     * in via the App\Traits\Auditable trait - one table instead of a
     * per-module history table (order_status_histories, subscription_history,
     * etc.) to avoid duplicating the same shape everywhere going forward.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('activity_log_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->uuid('branch_id')->nullable();

            // Who performed the action - integer to match users.id
            // (auto-increment), not the UUID PKs used by business-data tables.
            $table->integer('causer_id')->nullable();

            // The module/table this entry belongs to (e.g. 'expense',
            // 'order', 'journal_entry', 'auth') and the UUID (or id) of the
            // affected record.
            $table->string('module');
            $table->string('record_id')->nullable();

            $table->string('action');
            $table->string('description')->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamp('date_created')->nullable();

            $table->index(['business_id', 'module', 'record_id']);
            $table->index(['business_id', 'date_created']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};
