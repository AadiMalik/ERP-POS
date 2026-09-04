<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * JSON-encoded array of the specific serial numbers chosen for this sale
 * line, for variations with track_serial_number = true. Staged here at cart
 * save() time; consumed into product_variation_serial_numbers allocations
 * by OrderService::applyPostedEffects() when the order is actually posted
 * (same two-phase pattern as Purchase/GRN's serial_numbers columns). See
 * resources/docs/developer/18-serial-number-tracking.md.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->text('serial_numbers')->nullable()->after('product_variation_batch_id');
        });
    }

    public function down()
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn('serial_numbers');
        });
    }
};
