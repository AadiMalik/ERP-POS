<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_deductions', function (Blueprint $table) {
            $table->uuid('employee_deduction_id')->primary();
            $table->uuid('employee_id');
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->boolean('is_recurring')->default(false);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->uuid('business_id')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
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
        Schema::dropIfExists('employee_deductions');
    }
};
