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
        Schema::create('pos_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->nullable();
            $table->string('invoice_prefix')->default('INV-01012026-01');
            $table->integer('invoice_start')->default(1);
            $table->text('invoice_footer')->nullable();
            $table->integer('default_customer_id')->nullable();
            $table->integer('default_payment_method_id')->nullable();
            $table->boolean('enable_discount')->default(true);
            $table->boolean('auto_print_invoice')->default(true);
            $table->boolean('show_product_image')->default(true);

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
        Schema::dropIfExists('pos_settings');
    }
};
