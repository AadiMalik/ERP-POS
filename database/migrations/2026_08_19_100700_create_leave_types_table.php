<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->uuid('leave_type_id')->primary();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->unsignedInteger('max_days_per_year')->default(0);

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
        Schema::dropIfExists('leave_types');
    }
};
