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
        Schema::create('thermal_print_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->nullable();

            $table->boolean('is_enabled')->default(false);
            $table->unsignedSmallInteger('paper_width_mm')->default(80);

            $table->json('field_config')->nullable();
            $table->json('footer_config')->nullable();

            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('thermal_print_settings');
    }
};
