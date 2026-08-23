<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Defines the "get" side of a buy-X-get-Y voucher when it differs from the
     * "buy" scope. Empty = the free/discounted item comes from the same scope
     * as what was bought.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voucher_get_categories', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_id');
            $table->string('category_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('voucher_get_categories');
    }
};
