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
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('customer_id');
        });

        Schema::table('voucher_customers', function (Blueprint $table) {
            $table->dropColumn('customer_id');
        });

        Schema::table('voucher_redemptions', function (Blueprint $table) {
            $table->dropColumn('customer_id');
        });

        Schema::table('journal_entry_details', function (Blueprint $table) {
            $table->dropColumn('customer_id');
        });

        Schema::table('pos_settings', function (Blueprint $table) {
            $table->dropColumn('default_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_id')->nullable()->after('cashier_id');
        });

        Schema::table('voucher_customers', function (Blueprint $table) {
            $table->string('customer_id')->nullable()->after('voucher_id');
        });

        Schema::table('voucher_redemptions', function (Blueprint $table) {
            $table->string('customer_id')->nullable()->after('order_id');
        });

        Schema::table('journal_entry_details', function (Blueprint $table) {
            $table->string('customer_id', 36)->nullable();
        });

        Schema::table('pos_settings', function (Blueprint $table) {
            $table->string('default_customer_id', 36)->nullable();
        });
    }
};
