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
        Schema::table('pos_settings', function (Blueprint $table) {
            // Business-level default Automatic register mode window - a branch's own
            // open_time/close_time (see branches table) overrides these when set.
            $table->time('open_time')->nullable()->after('register_mode');
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
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->dropColumn(['open_time', 'close_time']);
        });
    }
};
