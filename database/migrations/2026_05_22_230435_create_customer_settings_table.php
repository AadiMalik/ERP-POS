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
        Schema::create('customer_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->nullable();
            $table->string('customer_code_prefix')->default('CUS-');
            $table->boolean('enable_credit_limit')->default(false);
            $table->decimal('credit_limit', 18, 2)->default(5000.00);
            $table->boolean('loyalty_program')->default(false);
            $table->decimal('loyalty_every_amount', 18, 2)->default(100);
            $table->decimal('loyalty_point_rate', 18, 2)->default(1.00);
            $table->decimal('loyalty_min_order_amount', 18, 2)->default(1000.00);

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
        Schema::dropIfExists('customer_settings');
    }
};
