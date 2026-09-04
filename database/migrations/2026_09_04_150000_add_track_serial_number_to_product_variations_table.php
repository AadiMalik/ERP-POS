<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opt-in per-variation serial number tracking flag, following the same
 * pattern as the existing track_batch/track_expiry columns. See
 * resources/docs/developer/18-serial-number-tracking.md.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->boolean('track_serial_number')->default(false)->after('track_expiry');
        });
    }

    public function down()
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn('track_serial_number');
        });
    }
};
