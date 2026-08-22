<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('service_sale_details', function (Blueprint $table) {
            $table->uuid('service_sale_detail_id')->primary();
            $table->uuid('service_sale_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->string('item_name')->nullable();
            $table->decimal('quantity', 18, 3)->default(1.000);
            $table->decimal('unit_price', 18, 3)->nullable();
            $table->decimal('discount', 18, 3)->default(0);
            $table->decimal('discount_amount', 18, 3)->default(0);
            $table->decimal('tax', 18, 3)->default(0);
            $table->decimal('tax_amount', 18, 3)->default(0);
            $table->decimal('subtotal', 18, 3)->nullable();
            $table->decimal('total', 18, 3)->nullable();
            $table->text('description')->nullable();

            $table->uuid('createdby_id')->nullable();
            $table->uuid('updatedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_sale_details');
    }
};
