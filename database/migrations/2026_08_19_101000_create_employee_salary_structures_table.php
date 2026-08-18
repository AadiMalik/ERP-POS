<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_salary_structures', function (Blueprint $table) {
            $table->uuid('employee_salary_structure_id')->primary();
            $table->uuid('employee_id');
            $table->date('effective_from');
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('overtime_rate_per_hour', 10, 2)->nullable();
            $table->enum('status', ['active', 'superseded'])->default('active');

            $table->uuid('business_id')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });

        Schema::create('employee_salary_structure_items', function (Blueprint $table) {
            $table->uuid('employee_salary_structure_item_id')->primary();
            $table->uuid('employee_salary_structure_id');
            $table->uuid('salary_component_id');
            $table->decimal('amount_or_percentage', 12, 2);
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_salary_structure_items');
        Schema::dropIfExists('employee_salary_structures');
    }
};
