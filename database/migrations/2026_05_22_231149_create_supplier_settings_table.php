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
        Schema::create('supplier_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->nullable();
            $table->string('supplier_code_prefix')->default('SUP-');
            $table->boolean('enable_credit_limit')->default(true);
            $table->decimal('credit_limit', 18, 2)->default(5000.00);
            $table->integer('default_payment_days')->default(30);
            
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
        Schema::dropIfExists('supplier_settings');
    }
};
