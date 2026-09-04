<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('manufacturing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->nullable();

            $table->boolean('is_manufacturing_enabled')->default(false);
            $table->boolean('default_raw_material_sellable')->default(true);
            $table->unsignedInteger('default_shelf_life_days')->nullable();
            $table->boolean('allow_overproduction')->default(false);
            $table->decimal('overproduction_max_percent', 8, 2)->default(0);

            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('manufacturing_settings');
    }
};
