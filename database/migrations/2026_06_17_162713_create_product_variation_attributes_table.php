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
        Schema::create('product_variation_attributes', function (Blueprint $table) {
            $table->uuid('product_variation_attribute_id')->primary();
            $table->uuid('product_variation_id')->nullable();
            $table->string('name')->nullable();
            $table->string('value')->nullable();

            $table->integer('createdby_id')->nullable();

            $table->timestamp('date_created')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_variation_attributes');
    }
};
