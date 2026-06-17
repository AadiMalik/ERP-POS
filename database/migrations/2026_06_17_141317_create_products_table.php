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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('product_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->uuid('category_id')->nullable();
            $table->uuid('sub_category_id')->nullable();
            $table->uuid('brand_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->enum('type',['single', 'variable','service'])->default('single');
            $table->enum('usage_type',['saleable', 'consumable','asset','service'])->default('saleable');
            $table->boolean('is_track_stock')->default(true);
            $table->boolean('is_pos_visible')->default(true);
            $table->boolean('is_website_visible')->default(true);
            $table->boolean('is_app_visible')->default(true);
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->boolean('is_featured')->default(false);
            
            $table->enum('status',['active', 'inactive'])->default('active');
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
