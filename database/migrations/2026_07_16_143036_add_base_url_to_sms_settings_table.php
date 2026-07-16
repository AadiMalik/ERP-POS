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
    public function up()
    {
        DB::statement("
            ALTER TABLE sms_settings
            MODIFY provider ENUM(
                'twilio',
                'infobip',
                'brandsms',
                'msg91',
                'vonage'
            ) DEFAULT 'twilio'
        ");
        Schema::table('sms_settings', function (Blueprint $table) {
            $table->string('base_url')->nullable();
            $table->string('account_sid')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('template_id')->nullable();
            $table->string('entity_id')->nullable();
            $table->string('flow_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("
            ALTER TABLE sms_settings
            MODIFY provider ENUM(
                'twilio','jazz','brandsms','msg91'
            ) DEFAULT 'twilio'
        ");
        Schema::table('sms_settings', function (Blueprint $table) {
            $table->dropColumn('base_url');
            $table->dropColumn('account_sid');
            $table->dropColumn('auth_token');
            $table->dropColumn('template_id');
            $table->dropColumn('entity_id');
            $table->dropColumn('flow_id');
        });
    }
};
