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
        Schema::create('order_payments', function (Blueprint $table) {
            $table->uuid('order_payment_id')->primary();
            $table->string('order_id');
            $table->string('payment_method_id');

            $table->decimal('amount', 18, 3)->default(0);
            $table->string('reference_no')->nullable();

            $table->boolean('is_deleted')->default(0);
            $table->integer('createdby_id')->nullable();
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
        Schema::dropIfExists('order_payments');
    }
};
