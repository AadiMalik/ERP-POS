<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->uuid('bank_reconciliation_id')->primary();
            $table->uuid('business_id');
            $table->uuid('branch_id')->nullable();
            $table->uuid('account_id');

            $table->date('period_from');
            $table->date('period_to');

            $table->decimal('statement_opening_balance', 15, 2)->default(0);
            $table->decimal('statement_closing_balance', 15, 2)->default(0);
            $table->decimal('book_balance', 15, 2)->nullable();
            $table->decimal('adjusted_book_balance', 15, 2)->nullable();
            $table->decimal('difference', 15, 2)->nullable();

            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->text('notes')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->integer('completed_by_id')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'account_id', 'status'], 'bank_rec_account_status_idx');
            $table->index(['business_id', 'period_from', 'period_to'], 'bank_rec_period_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bank_reconciliations');
    }
};
