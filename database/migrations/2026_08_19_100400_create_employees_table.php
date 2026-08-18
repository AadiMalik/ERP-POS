<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('employee_id')->primary();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('employee_code')->nullable();

            $table->uuid('department_id')->nullable();
            $table->uuid('designation_id')->nullable();
            $table->uuid('shift_id')->nullable();

            $table->date('joining_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])->default('full_time');

            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('marital_status')->nullable();
            $table->string('national_id')->nullable();
            $table->text('address')->nullable();

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();

            $table->string('bank_name')->nullable();
            $table->string('bank_account_title')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_branch_code')->nullable();
            $table->enum('payment_method', ['bank', 'cash'])->default('bank');

            $table->string('photo')->nullable();

            $table->enum('status', ['active', 'on_leave', 'suspended', 'resigned', 'terminated'])->default('active');
            $table->date('exit_date')->nullable();

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
        Schema::dropIfExists('employees');
    }
};
