<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * JSON-encoded array of serial numbers entered against a receiving line
 * (direct Purchase / GRN / Opening Stock), for variations with
 * track_serial_number = true. Staged here at save() time; consumed into
 * product_variation_serial_numbers rows by
 * ProductVariationSerialService::receiveSerials() when the line is actually
 * posted (purchase/GRN approval, or opening stock save). See
 * resources/docs/developer/18-serial-number-tracking.md.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->text('serial_numbers')->nullable()->after('product_variation_batch_id');
        });
        Schema::table('good_receipt_note_details', function (Blueprint $table) {
            $table->text('serial_numbers')->nullable()->after('expiry_date');
        });
        Schema::table('opening_stock_details', function (Blueprint $table) {
            $table->text('serial_numbers')->nullable()->after('product_variation_batch_id');
        });
    }

    public function down()
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropColumn('serial_numbers');
        });
        Schema::table('good_receipt_note_details', function (Blueprint $table) {
            $table->dropColumn('serial_numbers');
        });
        Schema::table('opening_stock_details', function (Blueprint $table) {
            $table->dropColumn('serial_numbers');
        });
    }
};
