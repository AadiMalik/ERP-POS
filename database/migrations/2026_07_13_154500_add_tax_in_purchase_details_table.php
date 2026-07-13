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
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->decimal('tax',18,3)->default(0);
            $table->decimal('tax_amount',18,3)->default(0);
            $table->decimal('discount',18,3)->default(0);
            $table->decimal('discount_amount',18,3)->default(0);
            $table->decimal('subtotal',18,3)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            //
        });
    }
};
