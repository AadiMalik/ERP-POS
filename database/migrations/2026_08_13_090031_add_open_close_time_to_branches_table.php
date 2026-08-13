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
        Schema::table('branches', function (Blueprint $table) {
            // Per-branch override for Automatic register mode's open/close window -
            // falls back to pos_settings.open_time/close_time when null.
            $table->time('open_time')->nullable()->after('status');
            $table->time('close_time')->nullable()->after('open_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['open_time', 'close_time']);
        });
    }
};
