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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->decimal('price', 18, 2)->default(0.00);
            $table->integer('order')->default(1);
            $table->enum('duration_type', ['monthly', 'yearly'])->default('monthly');
            $table->integer('duration_days')->default(30);
            $table->boolean('status')->default(true);
            $table->decimal('max_branches', 18, 2)->default(0.00);
            $table->decimal('max_users', 18, 2)->default(0.00);
            $table->decimal('max_customers', 18, 2)->default(0.00);
            $table->decimal('max_warehouses', 18, 2)->default(0.00);
            $table->decimal('max_categories', 18, 2)->default(0.00);
            $table->decimal('max_products', 18, 2)->default(0.00);
            $table->decimal('max_suppliers', 18, 2)->default(0.00);
            $table->decimal('max_purchase_orders', 18, 2)->default(0.00);
            $table->decimal('max_purchases', 18, 2)->default(0.00);
            $table->decimal('max_sales', 18, 2)->default(0.00);
            $table->decimal('max_transfers', 18, 2)->default(0.00);
            $table->decimal('max_expenses', 18, 2)->default(0.00);
            $table->decimal('max_vouchers', 18, 2)->default(0.00);

            $table->boolean('is_pos_enabled')->default(false);
            $table->boolean('is_inventory_enabled')->default(false);
            $table->boolean('is_accounting_enabled')->default(false);
            $table->boolean('is_hrm_enabled')->default(false);
            $table->boolean('is_payroll_enabled')->default(false);

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
        Schema::dropIfExists('packages');
    }
};
