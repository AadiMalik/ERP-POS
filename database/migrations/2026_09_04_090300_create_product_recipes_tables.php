<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recipe/BOM for a manufactured product/variation. Versioned copy-on-write:
 * editing an in-use recipe (referenced by any confirmed Manufacturing Plan or
 * Production) creates a new row instead of mutating the old one, so
 * already-confirmed plans/productions keep the exact recipe they locked at
 * confirm-time (see product_recipes.previous_version_id).
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->uuid('product_recipe_id')->primary();
            $table->uuid('business_id')->nullable()->index();
            $table->uuid('product_id')->nullable()->index();
            $table->uuid('product_variation_id')->nullable()->index();

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

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'product_variation_id', 'status', 'is_deleted'], 'recipe_biz_variation_status_idx');
        });

        Schema::create('product_recipe_items', function (Blueprint $table) {
            $table->uuid('product_recipe_item_id')->primary();
            $table->uuid('product_recipe_id')->index();

            $table->enum('item_type', ['component', 'byproduct'])->default('component');
            $table->uuid('raw_material_product_id')->nullable()->index();
            $table->uuid('raw_material_product_variation_id')->nullable()->index();
            $table->decimal('quantity', 18, 4)->default(0.0000);
            $table->uuid('unit_id')->nullable();
            $table->decimal('wastage_percentage', 8, 4)->nullable();
            $table->decimal('wastage_quantity', 18, 4)->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->text('notes')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
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
    }

    public function down()
    {
        Schema::dropIfExists('product_recipe_steps');
        Schema::dropIfExists('product_recipe_items');
        Schema::dropIfExists('product_recipes');
    }
};
