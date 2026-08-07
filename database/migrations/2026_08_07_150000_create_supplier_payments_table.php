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
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->uuid('supplier_payment_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->uuid('supplier_id')->nullable();
            $table->uuid('purchase_id')->nullable();

            $table->string('payment_no')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque', 'online'])->default('cash');

            $table->uuid('payment_account_id')->nullable();
            $table->uuid('supplier_account_id')->nullable();

            $table->string('reference_no')->nullable();
            $table->timestamp('cheque_date')->nullable();

            $table->decimal('amount', 18, 3)->default(0.000);
            $table->decimal('tax_amount', 18, 3)->default(0.000);
            $table->decimal('discount_amount', 18, 3)->default(0.000);
            $table->decimal('net_amount', 18, 3)->default(0.000);

            $table->text('remarks')->nullable();
            $table->string('attachment')->nullable();

            $table->enum('status', ['pending', 'posted', 'cancelled'])->default('pending');
            $table->integer('postedby_id')->nullable();
            $table->timestamp('date_posted')->nullable();

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
        Schema::dropIfExists('supplier_payments');
    }
};
