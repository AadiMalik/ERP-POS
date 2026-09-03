<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->uuid('bank_statement_line_id')->primary();
            $table->uuid('bank_reconciliation_id');

            $table->date('transaction_date');
            $table->decimal('amount', 15, 2);
            $table->string('reference', 191)->nullable();
            $table->string('description', 500)->nullable();

            $table->enum('match_status', ['unmatched', 'matched', 'ignored'])->default('unmatched');
            $table->uuid('matched_journal_entry_detail_id')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['bank_reconciliation_id', 'match_status'], 'bank_stmt_rec_status_idx');
            $table->index('matched_journal_entry_detail_id', 'bank_stmt_matched_jed_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};
