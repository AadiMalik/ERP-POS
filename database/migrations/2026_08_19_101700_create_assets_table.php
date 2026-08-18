<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('asset_id')->primary();
            $table->string('asset_tag')->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->uuid('product_id')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_value', 12, 2)->nullable();
            $table->enum('condition', ['new', 'good', 'fair', 'damaged'])->default('new');
            $table->enum('status', ['available', 'allocated', 'maintenance', 'retired'])->default('available');

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

        Schema::create('asset_allocations', function (Blueprint $table) {
            $table->uuid('asset_allocation_id')->primary();
            $table->uuid('asset_id');
            $table->uuid('employee_id');
            $table->date('issue_date');
            $table->date('expected_return_date')->nullable();
            $table->date('return_date')->nullable();
            $table->enum('condition_on_issue', ['new', 'good', 'fair', 'damaged'])->default('good');
            $table->enum('condition_on_return', ['new', 'good', 'fair', 'damaged'])->nullable();
            $table->enum('status', ['issued', 'returned', 'lost', 'damaged'])->default('issued');
            $table->string('remarks')->nullable();

            $table->uuid('business_id')->nullable();

            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('asset_allocations');
        Schema::dropIfExists('assets');
    }
};
