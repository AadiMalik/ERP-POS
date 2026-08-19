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
        // Append-only execution log - never updated or soft-deleted, so it
        // carries only createdby_id/date_created rather than the full
        // is_deleted/updatedby_id/deletedby_id audit block used on mutable
        // tables (mirrors activity_logs / subscription_reminder_logs).
        Schema::create('recurring_transaction_runs', function (Blueprint $table) {
            $table->uuid('recurring_transaction_run_id')->primary();
            $table->uuid('recurring_transaction_id');

            // The SCHEDULED occurrence date (not necessarily "today" - supports
            // catch-up runs), and the hard idempotency backstop below.
            $table->date('run_date');

            $table->enum('status', ['success', 'failed', 'skipped']);
            $table->string('generated_model_type', 50)->nullable(); // 'expense' | 'journal_entry'
            $table->uuid('generated_model_id')->nullable();
            $table->text('error_message')->nullable();

            $table->enum('triggered_by', ['scheduler', 'manual'])->default('scheduler');
            $table->integer('triggered_by_user_id')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->integer('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->unique(['recurring_transaction_id', 'run_date'], 'recurring_run_dedupe_unique');
            $table->index(['recurring_transaction_id', 'status'], 'recurring_run_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recurring_transaction_runs');
    }
};
