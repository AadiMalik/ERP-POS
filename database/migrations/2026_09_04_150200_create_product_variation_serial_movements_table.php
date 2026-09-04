<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only movement/audit log for serialized units - the per-unit
 * analogue of product_variation_stock_transactions. Powers the Serial
 * Number Details timeline and the Movement History report. See
 * resources/docs/developer/18-serial-number-tracking.md.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('product_variation_serial_movements', function (Blueprint $table) {
            $table->uuid('product_variation_serial_movement_id')->primary();
            $table->uuid('product_variation_serial_number_id')->nullable();
            $table->uuid('business_id')->nullable();

            $table->enum('event_type', [
                'purchased', 'opening_stock', 'transfer_sent', 'transfer_received', 'sold',
                'sale_returned', 'purchase_returned', 'damaged', 'wasted', 'expired',
                'sent_for_repair', 'returned_from_repair', 'replaced', 'decommissioned',
                'added_manually',
            ]);

            $table->uuid('from_warehouse_id')->nullable();
            $table->uuid('to_warehouse_id')->nullable();

            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();

            $table->text('notes')->nullable();

            $table->integer('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->index('product_variation_serial_number_id', 'pvsm_serial_idx');
            $table->index(['reference_type', 'reference_id'], 'pvsm_reference_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_variation_serial_movements');
    }
};
