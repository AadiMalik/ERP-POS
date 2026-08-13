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
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->uuid('order_status_history_id')->primary();
            $table->string('order_id');

            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('reason')->nullable();

            $table->integer('changedby_id')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_status_history');
    }
};
