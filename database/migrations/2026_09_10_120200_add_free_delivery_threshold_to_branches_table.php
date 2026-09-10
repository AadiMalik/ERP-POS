<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Orders at or above this amount get free delivery at this branch
     * (null = feature disabled for the branch). See
     * DeliveryZoneService::resolve().
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->decimal('free_delivery_min_order_amount', 12, 2)->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('free_delivery_min_order_amount');
        });
    }
};
