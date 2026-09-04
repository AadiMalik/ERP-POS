<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trims Manufacturing down to exactly the requested spec: a Recipe is a
 * single product_id+product_variation_id -> raw-material-lines mapping
 * edited directly in place (no versioning/copy-on-write, no wastage%, no
 * output_quantity/yield, no by-products, no production steps). Production
 * has no step-by-step execution. See resources/docs/developer/16-manufacturing.md.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('production_steps');
        Schema::dropIfExists('production_byproducts');
        Schema::dropIfExists('product_recipe_steps');

        Schema::table('product_recipes', function (Blueprint $table) {
            $table->dropColumn(['name', 'version', 'previous_version_id', 'status', 'effective_from', 'effective_to', 'output_quantity', 'output_unit_id', 'is_default', 'notes']);
            // A recipe is never soft-deleted (editing happens in place, see
            // ProductRecipeService::save()) so a plain unique holds - exactly
            // one recipe row per finished-good variation.
            $table->unique('product_variation_id', 'recipe_one_per_variation_unique');
        });

        Schema::table('product_recipe_items', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'wastage_percentage', 'wastage_quantity', 'sequence', 'notes']);
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn(['started_at']);
        });
    }

    public function down()
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable();
        });

        Schema::table('product_recipe_items', function (Blueprint $table) {
            $table->enum('item_type', ['component', 'byproduct'])->default('component');
            $table->decimal('wastage_percentage', 8, 4)->nullable();
            $table->decimal('wastage_quantity', 18, 4)->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->text('notes')->nullable();
        });

        Schema::table('product_recipes', function (Blueprint $table) {
            $table->dropUnique('recipe_one_per_variation_unique');
            $table->string('name')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->uuid('previous_version_id')->nullable();
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->decimal('output_quantity', 18, 4)->default(1.0000);
            $table->uuid('output_unit_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->text('notes')->nullable();
        });

        Schema::create('product_recipe_steps', function (Blueprint $table) {
            $table->uuid('product_recipe_step_id')->primary();
            $table->uuid('product_recipe_id')->index();
            $table->string('name');
            $table->unsignedInteger('sequence')->default(0);
            $table->unsignedInteger('default_duration_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_deleted')->default(false);
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
    }
};
