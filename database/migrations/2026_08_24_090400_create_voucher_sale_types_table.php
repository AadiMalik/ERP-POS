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
        // Scope pivot - an absent row for a voucher means it applies to all sale types.
        Schema::create('voucher_sale_types', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_id');
            $table->string('sale_type_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('voucher_sale_types');
    }
};
