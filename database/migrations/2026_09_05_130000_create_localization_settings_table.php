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
        Schema::create('localization_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->nullable();
            $table->string('display_language')->default('en');
            $table->string('input_language')->default('en');
            $table->enum('direction_override', ['auto', 'ltr', 'rtl'])->default('auto');

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
        Schema::dropIfExists('localization_settings');
    }
};
