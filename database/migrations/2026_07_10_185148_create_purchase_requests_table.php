<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->uuid('purchase_request_id')->primary();
            $table->uuid('supplier_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->uuid('business_id')->nullable();
            $table->uuid('warehouse_id')->nullable();
            $table->uuid('department_id')->nullable();
            $table->string('purchase_request_no')->nullable();
            $table->timestamp('purchase_request_date')->nullable();
            $table->timestamp('purchase_expected_date')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', [
                'draft',
                'pending',
                'approved',
                'quotation sent',
                'quotation received',
                'converted',
                'cancelled'
            ])->default('draft');

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_requests');
    }
};
