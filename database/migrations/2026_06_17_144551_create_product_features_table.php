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
        Schema::create('product_features', function (Blueprint $table) {
            $table->uuid('product_feature_id')->primary();
            $table->uuid('product_id')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->integer('sorting')->default(1);

            $table->enum('status', ['active', 'inactive'])->default('active');
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
        Schema::dropIfExists('product_features');
    }
};
