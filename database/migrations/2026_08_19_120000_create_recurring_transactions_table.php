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
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->uuid('recurring_transaction_id')->primary();
            $table->uuid('business_id');
            $table->uuid('branch_id')->nullable();

            // 'expense' | 'journal_entry' today - see App\Enums\RecurringTransactionType.
            // Kept as a plain string (not a DB enum) so future types (sales invoice,
            // purchase bill, payment, subscription billing) never require an
            // ALTER...MODIFY on this column, only a new registry entry.
            $table->string('transaction_type', 50);
            $table->string('name', 150);

            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->unsignedTinyInteger('weekday')->nullable(); // Carbon::SUNDAY(0)..SATURDAY(6), weekly only
            $table->unsignedTinyInteger('day_of_month')->nullable(); // 1-31, monthly & yearly
            $table->unsignedTinyInteger('month_of_year')->nullable(); // 1-12, yearly only

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('max_occurrences')->nullable();
            $table->unsignedInteger('occurrences_count')->default(0); // successful generations only

            $table->date('next_run_date')->nullable(); // null once status is completed/cancelled
            $table->date('last_run_date')->nullable(); // last successful run

            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->boolean('auto_post')->default(false);
            $table->text('notes')->nullable();

            // Type-specific payload (category/accounts/amount for expense; journal_id +
            // debit/credit lines for journal entry). See App\Support\Recurring\TemplateData\*.
            $table->json('template_data');

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'status', 'next_run_date'], 'recurring_txn_due_idx');
            $table->index(['business_id', 'transaction_type'], 'recurring_txn_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
