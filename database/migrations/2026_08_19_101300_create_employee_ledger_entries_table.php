<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_ledger_entries', function (Blueprint $table) {
            $table->uuid('employee_ledger_entry_id')->primary();
            $table->uuid('employee_id');
            $table->date('entry_date');
            $table->enum('type', ['salary', 'advance', 'deduction', 'payment', 'adjustment']);
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->string('description')->nullable();

            $table->uuid('business_id')->nullable();

            $table->integer('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_ledger_entries');
    }
};
