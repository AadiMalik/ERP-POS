<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A Manufacturing Plan is intent + reservation only - confirming it reserves
 * raw materials (manufacturing_plan_materials.reserved_quantity, mirrored
 * onto product_variation_stocks.reserved_quantity) but never touches finished
 * stock. Finished stock only increases when a linked Production completes.
 * produced_quantity/remaining_quantity/progress are derived from the
 * plan's productions, not stored redundantly here except produced_quantity
 * (kept as a running total for cheap listing/status queries).
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('manufacturing_plans', function (Blueprint $table) {
            $table->uuid('manufacturing_plan_id')->primary();
            $table->string('plan_no')->nullable();
            $table->uuid('business_id')->nullable()->index();
            $table->uuid('branch_id')->nullable()->index();
            $table->uuid('product_id')->nullable()->index();
            $table->uuid('product_variation_id')->nullable()->index();
            $table->uuid('product_recipe_id')->nullable()->index();
            $table->uuid('warehouse_id')->nullable()->index();

            $table->decimal('planned_quantity', 18, 4)->default(0.0000);
            $table->uuid('planned_unit_id')->nullable();
            $table->decimal('produced_quantity', 18, 4)->default(0.0000);

            $table->enum('status', [
                'draft', 'confirmed', 'materials_reserved', 'in_production',
                'partially_completed', 'completed', 'cancelled',
            ])->default('draft');

            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'branch_id', 'status', 'is_deleted'], 'mfg_plan_biz_branch_status_idx');
        });

        Schema::create('manufacturing_plan_materials', function (Blueprint $table) {
            $table->uuid('manufacturing_plan_material_id')->primary();
            $table->uuid('manufacturing_plan_id')->index();
            $table->uuid('product_id')->nullable()->index();
            $table->uuid('product_variation_id')->nullable()->index();
            $table->uuid('unit_id')->nullable();
            $table->uuid('warehouse_id')->nullable();

            $table->decimal('required_base_quantity', 18, 4)->default(0.0000);
            $table->decimal('reserved_quantity', 18, 4)->default(0.0000);
            $table->decimal('consumed_quantity', 18, 4)->default(0.0000);

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('manufacturing_plan_materials');
        Schema::dropIfExists('manufacturing_plans');
    }
};
