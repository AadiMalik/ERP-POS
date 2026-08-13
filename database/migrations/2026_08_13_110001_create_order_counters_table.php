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
        Schema::create('order_counters', function (Blueprint $table) {
            $table->id();
            $table->string('business_id');
            $table->string('branch_id');
            // A fixed sentinel date (1970-01-01) is used instead of NULL when
            // pos_settings.daily_order_id_reset = 'never', because MySQL unique
            // indexes treat every NULL as distinct - a real NULL here would let
            // concurrent requests create duplicate "perpetual" counter rows.
            $table->date('counter_date');
            $table->unsignedInteger('last_number')->default(0);

            $table->unique(['business_id', 'branch_id', 'counter_date'], 'order_counters_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_counters');
    }
};
