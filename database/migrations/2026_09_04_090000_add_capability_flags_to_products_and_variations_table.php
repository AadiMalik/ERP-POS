<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Independent purchasable/sellable/raw-material/manufactured flags for
 * Product & ProductVariation - the existing `usage_type` enum
 * (saleable/consumable/asset/service) cannot express "raw material that is
 * ALSO sold directly to customers", which Manufacturing requires (see
 * CLAUDE.md Manufacturing spec: a raw material used in a recipe must not
 * automatically become non-saleable). Backfilled from usage_type so existing
 * products keep their current sale/purchase behavior unchanged.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_purchasable')->default(true)->after('usage_type');
            $table->boolean('is_sellable')->default(true)->after('is_purchasable');
            $table->boolean('is_raw_material')->default(false)->after('is_sellable');
            $table->boolean('is_manufactured')->default(false)->after('is_raw_material');
        });

        Schema::table('product_variations', function (Blueprint $table) {
            $table->boolean('is_purchasable')->default(true)->after('track_expiry');
            $table->boolean('is_sellable')->default(true)->after('is_purchasable');
            $table->boolean('is_raw_material')->default(false)->after('is_sellable');
            $table->boolean('is_manufactured')->default(false)->after('is_raw_material');
        });

        // Backfill from the existing usage_type so current behavior is preserved.
        DB::table('products')->where('usage_type', 'consumable')->update([
            'is_sellable' => false,
            'is_raw_material' => true,
        ]);
        DB::table('products')->where('usage_type', 'asset')->update([
            'is_sellable' => false,
            'is_purchasable' => true,
        ]);
        DB::table('products')->where('usage_type', 'service')->update([
            'is_purchasable' => false,
        ]);

        $consumableVariationIds = DB::table('products')
            ->where('usage_type', 'consumable')
            ->pluck('product_id');
        if ($consumableVariationIds->isNotEmpty()) {
            DB::table('product_variations')
                ->whereIn('product_id', $consumableVariationIds)
                ->update(['is_sellable' => false, 'is_raw_material' => true]);
        }
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_purchasable', 'is_sellable', 'is_raw_material', 'is_manufactured']);
        });
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn(['is_purchasable', 'is_sellable', 'is_raw_material', 'is_manufactured']);
        });
    }
};
