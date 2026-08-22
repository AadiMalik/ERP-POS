<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('service_sales', function (Blueprint $table) {
            $table->uuid('service_sale_id')->primary();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->uuid('business_id')->nullable();
            $table->string('service_sale_no')->nullable();
            $table->timestamp('service_sale_date')->nullable();
            $table->decimal('subtotal', 18, 3)->default(0.000);
            $table->decimal('discount', 18, 3)->default(0.000);
            $table->decimal('discount_amount', 18, 3)->default(0.000);
            $table->decimal('tax', 18, 3)->default(0.000);
            $table->decimal('tax_amount', 18, 3)->default(0.000);
            $table->decimal('total', 18, 3)->default(0.000);
            $table->text('description')->nullable();

            $table->enum('status', ['pending', 'approved', 'cancelled'])->default('pending');

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
        Schema::dropIfExists('service_sales');
    }
};
