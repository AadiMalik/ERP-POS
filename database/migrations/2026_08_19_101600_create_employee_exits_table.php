<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_exits', function (Blueprint $table) {
            $table->uuid('employee_exit_id')->primary();
            $table->uuid('employee_id');
            $table->enum('type', ['resignation', 'termination']);
            $table->date('request_date');
            $table->unsignedInteger('notice_period_days')->default(0);
            $table->date('last_working_date')->nullable();
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'finalized'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->decimal('final_settlement_amount', 12, 2)->nullable();

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

        Schema::create('exit_clearances', function (Blueprint $table) {
            $table->uuid('exit_clearance_id')->primary();
            $table->uuid('employee_exit_id');
            $table->string('area');
            $table->enum('status', ['pending', 'cleared', 'rejected'])->default('pending');
            $table->unsignedBigInteger('cleared_by')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamp('date_created')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('exit_clearances');
        Schema::dropIfExists('employee_exits');
    }
};
