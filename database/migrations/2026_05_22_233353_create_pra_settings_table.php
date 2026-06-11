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
        Schema::create('pra_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->nullable();
            $table->boolean('enable_pra')->default(false);
            $table->string('pra_code_prefix')->default('PRA-');
            $table->string('pra_registration_no')->nullable();
            $table->text('pra_api_key')->nullable();
            
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
        Schema::dropIfExists('pra_settings');
    }
};
