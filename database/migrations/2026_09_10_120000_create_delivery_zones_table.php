<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distance-band delivery fees per branch (e.g. 0-5km => 200,
     * 5.1-8km => 350). See DeliveryZoneService::resolve() for how a
     * customer's delivery coordinates are matched against these bands.
     */
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->uuid('delivery_zone_id')->primary();
            $table->string('name')->nullable();
            $table->decimal('min_km', 8, 3)->default(0);
            $table->decimal('max_km', 8, 3);
            $table->decimal('fee', 12, 2)->default(0);
            $table->integer('sort_order')->default(0);

            $table->uuid('business_id')->nullable();
            $table->uuid('branch_id')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['branch_id', 'status', 'is_deleted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
