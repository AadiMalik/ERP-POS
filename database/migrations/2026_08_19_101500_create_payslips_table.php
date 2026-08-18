<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->uuid('payslip_id')->primary();
            $table->uuid('payroll_run_id');
            $table->uuid('employee_id');

            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('total_earnings', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('overtime_amount', 12, 2)->default(0);
            $table->decimal('advance_deduction', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);

            $table->unsignedInteger('present_days')->default(0);
            $table->unsignedInteger('absent_days')->default(0);
            $table->unsignedInteger('leave_days')->default(0);

            $table->enum('status', ['generated', 'paid'])->default('generated');
            $table->timestamp('paid_at')->nullable();

            $table->uuid('business_id')->nullable();
            $table->uuid('branch_id')->nullable();

            $table->integer('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();

            $table->unique(['payroll_run_id', 'employee_id']);
        });

        Schema::create('payslip_items', function (Blueprint $table) {
            $table->uuid('payslip_item_id')->primary();
            $table->uuid('payslip_id');
            $table->string('component_name');
            $table->enum('type', ['earning', 'deduction']);
            $table->decimal('amount', 12, 2);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payslip_items');
        Schema::dropIfExists('payslips');
    }
};
