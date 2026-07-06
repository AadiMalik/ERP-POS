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
        Schema::create('purchases', function (Blueprint $table) {
            $table->uuid('purchase_id')->primary();
            $table->uuid('supplier_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->uuid('business_id')->nullable();
            $table->uuid('warehouse_id')->nullable();
            $table->uuid('purchase_order_id')->nullable();
            $table->string('purchase_no')->nullable();
            $table->timestamp('purchase_date')->nullable();
            $table->enum('purchase_type', ['purchase_order', 'direct'])->default('direct');
            $table->decimal('subtotal', 18, 3)->default(0.000);
            $table->decimal('discount', 18, 3)->default(0.000);
            $table->decimal('discount_amount', 18, 3)->default(0.000);
            $table->decimal('tax', 18, 3)->default(0.000);
            $table->decimal('tax_amount', 18, 3)->default(0.000);
            $table->decimal('shipping_charge', 18, 3)->default(0.000);
            $table->decimal('total', 18, 3)->default(0.000);
            $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('unpaid');
            $table->text('description')->nullable();

            $table->enum('status', ['pending', 'approved', 'received', 'completed', 'cancelled'])->default('pending');

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
        Schema::dropIfExists('purchases');
    }
};
