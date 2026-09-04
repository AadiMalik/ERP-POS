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
        Schema::create('waste_damage_expiry_details', function (Blueprint $table) {
            $table->uuid('waste_damage_expiry_detail_id')->primary();
            $table->uuid('waste_damage_expiry_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->uuid('product_variation_id')->nullable();
            $table->uuid('unit_id')->nullable();
            $table->uuid('product_variation_batch_id')->nullable();
            $table->string('batch_no')->nullable();
            $table->date('expiry_date')->nullable();

            $table->decimal('quantity', 18, 3)->default(0.000);
            $table->decimal('unit_cost', 18, 3)->default(0.000);
            $table->decimal('value', 18, 3)->default(0.000);

            $table->enum('loss_type', ['waste', 'damaged', 'expired', 'spoiled', 'broken', 'lost', 'other'])->default('waste');
            $table->uuid('loss_reason_id')->nullable();
            $table->text('notes')->nullable();

            $table->uuid('createdby_id')->nullable();
            $table->uuid('updatedby_id')->nullable();
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
        Schema::dropIfExists('waste_damage_expiry_details');
    }
};
