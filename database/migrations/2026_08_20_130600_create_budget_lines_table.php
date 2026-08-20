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
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->uuid('budget_line_id')->primary();
            $table->uuid('budget_id');
            $table->uuid('account_id');
            $table->uuid('branch_id')->nullable(); // null = whole-business line

            $table->date('period_start'); // first day of the month/quarter/year this line covers
            $table->date('period_end');

            // Snapshot from AccountClassifier::isDebitNormal() at write time,
            // so variance comparisons never need to re-join the Chart of
            // Accounts to work out which side of the ledger is "up".
            $table->boolean('account_debit_normal');
            $table->decimal('budgeted_amount', 18, 2)->default(0); // signed in the account's normal-balance direction

            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();

            $table->unique(['budget_id', 'account_id', 'branch_id', 'period_start'], 'budget_line_unique');
            $table->index(['budget_id', 'period_start', 'period_end'], 'budget_line_period_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('budget_lines');
    }
};
