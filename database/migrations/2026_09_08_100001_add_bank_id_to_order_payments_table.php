<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which Bank a card/bank payment line was collected into, so an order's
     * payment breakdown (and the Orders list's Bank filter) can trace it.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->uuid('bank_id')->nullable()->after('payment_method_id');
            $table->index('bank_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropIndex(['bank_id']);
            $table->dropColumn('bank_id');
        });
    }
};
