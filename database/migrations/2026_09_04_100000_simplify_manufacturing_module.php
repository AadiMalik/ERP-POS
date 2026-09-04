<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simplification per business feedback:
 * - Each recipe item (raw material line) now carries its own consumption
 *   warehouse - a Manufacturing Plan no longer needs a single plan-wide
 *   warehouse (removed from manufacturing_plans).
 * - Overproduction is always blocked (no toggle/override) - the columns it
 *   needed on productions are removed.
 * - manufacturing_settings is removed entirely - Manufacturing is gated by
 *   the package-level module toggle only (same as HRM/Payroll), no separate
 *   business-level on/off + defaults screen.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('product_recipe_items', function (Blueprint $table) {
            $table->uuid('warehouse_id')->nullable()->after('unit_id')->index();
        });

        Schema::table('manufacturing_plans', function (Blueprint $table) {
            $table->dropColumn('warehouse_id');
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn(['is_overproduction', 'overproduction_reason']);
        });

        Schema::dropIfExists('manufacturing_settings');
    }

    public function down()
    {
        Schema::create('manufacturing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->nullable();
            $table->boolean('is_manufacturing_enabled')->default(false);
            $table->boolean('default_raw_material_sellable')->default(true);
            $table->unsignedInteger('default_shelf_life_days')->nullable();
            $table->boolean('allow_overproduction')->default(false);
            $table->decimal('overproduction_max_percent', 8, 2)->default(0);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->boolean('is_overproduction')->default(false);
            $table->text('overproduction_reason')->nullable();
        });

        Schema::table('manufacturing_plans', function (Blueprint $table) {
            $table->uuid('warehouse_id')->nullable()->index();
        });

        Schema::table('product_recipe_items', function (Blueprint $table) {
            $table->dropColumn('warehouse_id');
        });
    }
};
