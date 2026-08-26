<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('website_carts', function (Blueprint $table) {
            $table->uuid('cart_id')->primary();
            $table->string('business_id');
            $table->unsignedBigInteger('user_id');
            $table->string('branch_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();

            $table->unique(['business_id', 'user_id']);
            $table->index('business_id');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('website_carts');
    }
};
