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
        Schema::create('subscription_history', function (Blueprint $table) {
            $table->uuid('subscription_history_id')->primary();
            $table->string('business_id')->nullable();
            $table->string('business_subscription_id')->nullable();
            $table->string('event_type');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->string('from_package_id')->nullable();
            $table->string('to_package_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();

            $table->integer('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->index(['business_id']);
            $table->index(['business_subscription_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_history');
    }
};
