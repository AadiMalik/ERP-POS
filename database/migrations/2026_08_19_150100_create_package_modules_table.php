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
        Schema::create('package_modules', function (Blueprint $table) {
            $table->id();
            $table->uuid('package_id');
            $table->string('module_key');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_unlimited')->default(false);
            $table->integer('limit_value')->nullable();
            $table->timestamps();

            $table->unique(['package_id', 'module_key']);
            $table->foreign('package_id')->references('package_id')->on('packages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('package_modules');
    }
};
