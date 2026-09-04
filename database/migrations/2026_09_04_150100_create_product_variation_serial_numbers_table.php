<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per physical serialized unit - the current-state sibling of
 * product_variation_batches, for variations with track_serial_number = true.
 * Full history lives in product_variation_serial_movements; this table only
 * holds where/what-status a unit is right now. See
 * resources/docs/developer/18-serial-number-tracking.md.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('product_variation_serial_numbers', function (Blueprint $table) {
            $table->uuid('product_variation_serial_number_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->uuid('product_variation_id')->nullable();
            $table->uuid('warehouse_id')->nullable();
            $table->string('serial_no');

            $table->enum('status', [
                'available', 'in_transit', 'sold', 'returned_to_supplier',
                'damaged', 'wasted', 'expired', 'under_repair', 'replaced', 'decommissioned',
            ])->default('available');

            $table->decimal('avg_price', 18, 4)->default(0.0000);

            $table->enum('source_reference_type', ['purchase', 'grn', 'opening_stock'])->nullable();
            $table->uuid('source_reference_id')->nullable();
            $table->uuid('source_detail_id')->nullable();

            $table->uuid('current_transfer_note_detail_id')->nullable();
            $table->uuid('current_order_id')->nullable();
            $table->uuid('current_order_detail_id')->nullable();
            $table->uuid('current_customer_id')->nullable();

            $table->date('warranty_expires_at')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->unique(['business_id', 'serial_no'], 'pvsn_business_serial_unique');
            $table->index(['product_variation_id', 'warehouse_id', 'status'], 'pvsn_variation_warehouse_status_idx');
            $table->index('current_order_detail_id', 'pvsn_order_detail_idx');
            $table->index('current_customer_id', 'pvsn_customer_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_variation_serial_numbers');
    }
};
