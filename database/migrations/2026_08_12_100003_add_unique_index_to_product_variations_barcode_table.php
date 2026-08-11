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
     * Kept separate from the column-add migration so a duplicate-barcode
     * sanity check / backfill can be run first if this fails on existing data.
     * MySQL/MariaDB unique indexes allow multiple NULLs, so this is safe to
     * add even before every row has a generated barcode - but the existing
     * variation form has always saved an empty barcode as '' rather than NULL,
     * and unlike NULL, MySQL treats repeated empty strings as duplicates. Those
     * are normalized to NULL first so the constraint doesn't fail on legacy data.
     *
     * @return void
     */
    public function up()
    {
        DB::table('product_variations')->where('barcode', '')->update(['barcode' => null]);

        Schema::table('product_variations', function (Blueprint $table) {
            $table->unique(['business_id', 'barcode'], 'product_variations_business_barcode_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropUnique('product_variations_business_barcode_unique');
        });
    }
};
