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
        Schema::create('fbr_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->nullable();
            $table->boolean('enable_fbr')->default(false);
            $table->enum('fbr_environment', ['sandbox', 'production'])->default('sandbox');
            $table->string('fbr_pos_id')->nullable();
            $table->text('fbr_license_key')->nullable();
            $table->string('fbr_ntn')->nullable();
            $table->string('fbr_strn')->nullable();
            $table->text('fbr_sandbox_url')->nullable();
            $table->text('fbr_production_url')->nullable();
            
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
        Schema::dropIfExists('fbr_settings');
    }
};
