<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->uuid('employee_advance_id')->primary();
            $table->uuid('employee_id');
            $table->decimal('amount', 12, 2);
            $table->string('reason')->nullable();
            $table->date('request_date');
            $table->enum('status', ['pending', 'approved', 'rejected', 'repaying', 'completed'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('installments_count')->default(1);
            $table->decimal('installment_amount', 12, 2)->default(0);
            $table->decimal('remaining_balance', 12, 2)->default(0);

            $table->uuid('business_id')->nullable();
            $table->uuid('branch_id')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_advances');
    }
};
