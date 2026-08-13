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
        Schema::create('voucher_redemptions', function (Blueprint $table) {
            $table->uuid('voucher_redemption_id')->primary();
            $table->string('voucher_id');
            // nullable - no orders table exists yet (built in a later phase)
            $table->string('order_id')->nullable();
            $table->string('customer_id')->nullable();
            $table->decimal('discount_amount', 18, 3)->default(0.000);

            // redemptions are either active or soft-deleted-on-reversal, never edited -
            // no date_updated/date_deleted/updatedby_id/deletedby_id needed
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('date_created')->nullable();
            $table->integer('createdby_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('voucher_redemptions');
    }
};
