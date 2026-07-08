<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up(): void
    {
        DB::statement("
        ALTER TABLE business_settings
        MODIFY date_format ENUM(
            'd-m-Y',
            'd/m/Y',
            'd.m.Y',
            'm-d-Y',
            'm/d/Y',
            'm.d.Y',
            'Y-m-d',
            'Y/m/d',
            'Y.m.d',
            'd M Y',
            'M d, Y',
            'F d, Y',
            'd F Y',
            'j M Y',
            'j F Y'
        ) NOT NULL DEFAULT 'd-m-Y'
    ");

        DB::statement("
        ALTER TABLE business_settings
        MODIFY time_format ENUM(
            'H:i',
            'H:i:s',
            'h:i A',
            'h:i:s A',
            'g:i A',
            'g:i:s A'
        ) NOT NULL DEFAULT 'H:i'
    ");
    }

    public function down(): void
    {
        DB::statement("
        ALTER TABLE business_settings
        MODIFY date_format ENUM(
            'd-m-Y',
            'm-d-Y',
            'Y-m-d',
            'd/m/Y',
            'm/d/Y'
        ) NOT NULL DEFAULT 'd-m-Y'
    ");

        DB::statement("
        ALTER TABLE business_settings
        MODIFY time_format ENUM(
            '12',
            '24'
        ) NOT NULL DEFAULT '24'
    ");
    }
};
