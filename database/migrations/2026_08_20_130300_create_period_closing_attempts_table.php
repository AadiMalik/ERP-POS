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
        // Append-only log, mirrors recurring_transaction_runs - never updated
        // or soft-deleted, so it carries only createdby_id/date_created.
        Schema::create('period_closing_attempts', function (Blueprint $table) {
            $table->uuid('period_closing_attempt_id')->primary();
            $table->uuid('accounting_period_id');

            $table->date('attempt_date'); // scheduler run date - idempotency key alongside the period
            $table->enum('trigger', ['scheduler', 'manual']);
            $table->integer('triggered_by_id')->nullable();

            $table->enum('result', ['closed', 'blocked'])->default('blocked');

            $table->integer('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->unique(['accounting_period_id', 'attempt_date'], 'closing_attempt_dedupe_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('period_closing_attempts');
    }
};
