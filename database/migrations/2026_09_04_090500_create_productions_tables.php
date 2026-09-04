<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A Production is one independent manufacturing batch/run against a parent
 * Manufacturing Plan - its own quantity, warehouse, batch/lot, manufacturing
 * & expiry dates. A plan can have many productions (different warehouses,
 * batches, dates each). Only Production::complete() ever writes
 * product_variation_stock_transactions rows; the plan itself never does.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('productions', function (Blueprint $table) {
            $table->uuid('production_id')->primary();
            $table->string('production_no')->nullable();
            $table->uuid('business_id')->nullable()->index();
            $table->uuid('branch_id')->nullable()->index();
            $table->uuid('manufacturing_plan_id')->nullable()->index();
            $table->uuid('product_recipe_id')->nullable()->index();
            $table->uuid('warehouse_id')->nullable()->index();

            $table->decimal('quantity', 18, 4)->default(0.0000);
            $table->uuid('unit_id')->nullable();
            $table->string('batch_no')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->enum('status', ['draft', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->integer('operator_user_id')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_overproduction')->default(false);
            $table->text('overproduction_reason')->nullable();

            $table->decimal('material_cost', 18, 4)->default(0.0000);
            $table->decimal('labor_cost', 18, 4)->default(0.0000);
            $table->decimal('overhead_cost', 18, 4)->default(0.0000);
            $table->decimal('other_cost', 18, 4)->default(0.0000);
            $table->decimal('total_cost', 18, 4)->default(0.0000);
            $table->decimal('unit_cost', 18, 4)->default(0.0000);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'manufacturing_plan_id', 'status', 'is_deleted'], 'production_biz_plan_status_idx');
        });

        Schema::create('production_consumptions', function (Blueprint $table) {
            $table->uuid('production_consumption_id')->primary();
            $table->uuid('production_id')->index();
            $table->uuid('product_id')->nullable()->index();
            $table->uuid('product_variation_id')->nullable()->index();
            $table->uuid('product_variation_batch_id')->nullable()->index();
            $table->uuid('warehouse_id')->nullable();

            $table->decimal('base_quantity', 18, 4)->default(0.0000);
            $table->decimal('unit_cost', 18, 4)->default(0.0000);
            $table->decimal('total_cost', 18, 4)->default(0.0000);
            $table->uuid('product_variation_stock_transaction_id')->nullable()->index('prod_consumption_transaction_idx');

            $table->timestamp('date_created')->nullable();
        });

        Schema::create('production_steps', function (Blueprint $table) {
            $table->uuid('production_step_id')->primary();
            $table->uuid('production_id')->index();

            $table->string('name');
            $table->unsignedInteger('sequence')->default(0);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped'])->default('pending');
            $table->integer('operator_user_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
        });

        Schema::create('production_byproducts', function (Blueprint $table) {
            $table->uuid('production_byproduct_id')->primary();
            $table->uuid('production_id')->index();
            $table->uuid('product_id')->nullable()->index();
            $table->uuid('product_variation_id')->nullable()->index();

            $table->decimal('quantity', 18, 4)->default(0.0000);
            $table->uuid('warehouse_id')->nullable();
            $table->string('batch_no')->nullable();
            $table->uuid('product_variation_stock_transaction_id')->nullable()->index('prod_byproduct_transaction_idx');

            $table->timestamp('date_created')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('production_byproducts');
        Schema::dropIfExists('production_steps');
        Schema::dropIfExists('production_consumptions');
        Schema::dropIfExists('productions');
    }
};
