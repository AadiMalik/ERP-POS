<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::create('cost_price_adjustments', function (Blueprint $table) {
            $table->uuid('cost_price_adjustment_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->uuid('warehouse_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->uuid('product_variation_id')->nullable();
            $table->string('reference_no')->nullable();
            $table->date('adjustment_date')->nullable();

            $table->decimal('previous_cost_price', 18, 4)->default(0.0000);
            $table->decimal('new_cost_price', 18, 4)->default(0.0000);
            $table->decimal('quantity_on_hand', 18, 4)->default(0.0000);
            $table->decimal('difference_per_unit', 18, 4)->default(0.0000);
            $table->decimal('total_adjustment_amount', 18, 4)->default(0.0000);

            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('reference')->nullable();

            $table->enum('status', ['pending', 'approved', 'cancelled'])->default('pending');
            $table->integer('approvedby_id')->nullable();
            $table->timestamp('date_approved')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index('business_id');
            $table->index('product_id');
            $table->index('product_variation_id');
            $table->index('warehouse_id');
            $table->index('adjustment_date');
            $table->index('reference_no');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cost_price_adjustments');
    }
};
