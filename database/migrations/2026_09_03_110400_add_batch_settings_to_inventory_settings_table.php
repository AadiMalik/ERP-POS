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
        Schema::table('inventory_settings', function (Blueprint $table) {
            $table->boolean('block_expired_sale')->default(true)->after('enable_expiry_date');
            $table->string('batch_selection_strategy')->default('fefo')->after('block_expired_sale');
            $table->integer('near_expiry_days')->default(30)->after('batch_selection_strategy');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventory_settings', function (Blueprint $table) {
            $table->dropColumn(['block_expired_sale', 'batch_selection_strategy', 'near_expiry_days']);
        });
    }
};
