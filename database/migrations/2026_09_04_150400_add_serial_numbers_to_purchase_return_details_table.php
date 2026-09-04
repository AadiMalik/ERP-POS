<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * JSON-encoded array of the specific serial numbers being returned to the
 * supplier for a return line, for variations with track_serial_number =
 * true. See resources/docs/developer/18-serial-number-tracking.md.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('purchase_return_details', function (Blueprint $table) {
            $table->text('serial_numbers')->nullable()->after('description');
        });
    }

    public function down()
    {
        Schema::table('purchase_return_details', function (Blueprint $table) {
            $table->dropColumn('serial_numbers');
        });
    }
};
