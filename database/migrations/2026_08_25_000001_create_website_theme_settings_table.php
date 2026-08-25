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
        Schema::create('website_theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->unique();

            $table->string('theme_preset')->default('theme1');

            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('accent_color')->nullable();
            $table->string('background_color')->nullable();
            $table->string('surface_color')->nullable();
            $table->string('text_color')->nullable();
            $table->string('heading_color')->nullable();
            $table->string('border_color')->nullable();
            $table->string('success_color')->nullable();
            $table->string('warning_color')->nullable();
            $table->string('error_color')->nullable();

            $table->string('font_pairing')->nullable();
            $table->string('font_size_base')->default('md');
            $table->string('button_style')->default('soft_pill');
            $table->string('typography_style')->default('comfortable');

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
        Schema::dropIfExists('website_theme_settings');
    }
};
