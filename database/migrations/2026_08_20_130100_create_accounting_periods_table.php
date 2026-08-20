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
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->uuid('accounting_period_id')->primary();
            $table->uuid('business_id');
            $table->uuid('fiscal_year_id');

            $table->string('name', 100); // "August 2026" / "FY 2026-27"
            $table->enum('cadence', ['monthly', 'yearly', 'manual']); // snapshot of the setting at creation time
            $table->date('start_date');
            $table->date('end_date');

            $table->enum('status', ['upcoming', 'open', 'pending_close', 'closed'])->default('upcoming');

            $table->timestamp('opened_at')->nullable();
            $table->integer('opened_by_id')->nullable();

            $table->timestamp('closed_at')->nullable();
            $table->integer('closed_by_id')->nullable();
            $table->text('close_reason')->nullable();
            $table->boolean('closed_automatically')->default(false);

            $table->timestamp('reopened_at')->nullable();
            $table->integer('reopened_by_id')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->unsignedInteger('reopen_count')->default(0);

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->unique(['business_id', 'start_date', 'end_date'], 'period_range_unique');
            $table->index(['business_id', 'status'], 'period_status_idx');
            $table->index(['business_id', 'start_date', 'end_date'], 'period_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounting_periods');
    }
};
