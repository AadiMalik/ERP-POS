<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Denormalized fast-read total, kept in sync inside the same
     * transaction as every product_shares insert (see
     * App\Services\Concrete\Api\ProductShareService::record()). The
     * per-platform breakdown itself always comes from a live GROUP BY on
     * product_shares - only the total lives here.
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('share_count')->default(0)->after('is_loyalty_enabled');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('share_count');
        });
    }
};
