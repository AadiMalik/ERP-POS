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
        // The specific blocking items found on a failed closing attempt, so
        // the Accounting Period screen can show "why didn't this close".
        // accounting_period_id is denormalized (also reachable via
        // period_closing_attempt_id) so the "current open issues for this
        // period" query never needs to join through the attempts table.
        Schema::create('period_closing_issues', function (Blueprint $table) {
            $table->uuid('period_closing_issue_id')->primary();
            $table->uuid('period_closing_attempt_id');
            $table->uuid('accounting_period_id');

            $table->string('check_key', 60); // 'unposted_journal_entries' | 'pending_purchase_returns' | ...
            $table->string('source_type', 60)->nullable(); // e.g. 'PurchaseReturn', 'LeaveRequest' - for deep-linking
            $table->uuid('source_id')->nullable();
            $table->string('summary', 255); // "Purchase Return PR-0042 is pending approval"

            $table->timestamp('date_created')->nullable();

            $table->index(['accounting_period_id'], 'period_closing_issue_period_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('period_closing_issues');
    }
};
