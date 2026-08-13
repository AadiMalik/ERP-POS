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
        Schema::create('discounts', function (Blueprint $table) {
            $table->uuid('discount_id')->primary();
            $table->string('business_id');
            // nullable - supports both a customer-facing code AND a manual cashier-applied discount with no code
            $table->string('code')->nullable();
            $table->string('name');
            $table->enum('type', ['percent', 'fixed']);
            $table->decimal('value', 18, 3);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->integer('usage_limit_total')->nullable();
            $table->integer('usage_limit_per_customer')->nullable();
            $table->integer('used_count')->default(0);
            $table->decimal('min_order_amount', 18, 3)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');

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
        Schema::dropIfExists('discounts');
    }
};
