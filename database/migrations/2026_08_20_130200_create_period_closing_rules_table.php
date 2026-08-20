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
        // One row per business - which closing-checklist checks run before an
        // auto/manual close is allowed to proceed. Deliberately no
        // check_unreconciled_bank_items column - bank reconciliation does not
        // exist in this app yet; the checklist engine (PeriodClosingService)
        // is written to make adding a new check here a config-only change.
        Schema::create('period_closing_rules', function (Blueprint $table) {
            $table->uuid('period_closing_rule_id')->primary();
            $table->uuid('business_id')->unique();

            $table->boolean('check_unposted_journal_entries')->default(true);
            $table->boolean('check_pending_purchase_returns')->default(true);
            $table->boolean('check_pending_leave_requests')->default(true);
            $table->boolean('check_pending_employee_advances')->default(true);
            $table->boolean('check_pending_employee_exits')->default(true);

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
        Schema::dropIfExists('period_closing_rules');
    }
};
