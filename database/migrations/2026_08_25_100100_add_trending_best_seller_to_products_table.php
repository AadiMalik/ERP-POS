<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_trending')->default(false)->after('is_featured');
            $table->boolean('is_best_seller')->default(false)->after('is_trending');
        });

        // The original products migration (2026_06_17_141317_create_products_table.php)
        // did not add a unique index on (business_id, slug). Add one here, guarding
        // against pre-existing duplicate slugs (unlikely - this is pre-launch data -
        // but handled gracefully rather than letting the migration hard-fail).
        $indexExists = collect(DB::select("SHOW INDEX FROM products WHERE Key_name = 'products_business_id_slug_unique'"))->isNotEmpty();

        if (!$indexExists) {
            $duplicateSlugs = DB::table('products')
                ->select('business_id', 'slug')
                ->whereNotNull('slug')
                ->groupBy('business_id', 'slug')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            // Deduplicate by suffixing later duplicates with their product_id so
            // the unique index can be safely added even if stray duplicates exist.
            foreach ($duplicateSlugs as $dup) {
                $rows = DB::table('products')
                    ->where('business_id', $dup->business_id)
                    ->where('slug', $dup->slug)
                    ->orderBy('date_created')
                    ->get(['product_id', 'slug']);

                foreach ($rows->skip(1) as $row) {
                    DB::table('products')
                        ->where('product_id', $row->product_id)
                        ->update(['slug' => $row->slug . '-' . substr($row->product_id, 0, 8)]);
                }
            }

            Schema::table('products', function (Blueprint $table) {
                $table->unique(['business_id', 'slug'], 'products_business_id_slug_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_trending', 'is_best_seller']);
            $table->dropUnique('products_business_id_slug_unique');
        });
    }
};
