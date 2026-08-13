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
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->uuid('subscription_invoice_id')->primary();
            $table->string('business_id')->nullable();
            $table->string('business_subscription_id')->nullable();
            $table->string('package_id')->nullable();
            $table->string('invoice_no')->unique();
            $table->enum('billing_cycle', ['monthly', 'yearly', 'custom'])->default('monthly');
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0.00);
            $table->decimal('discount', 18, 2)->default(0.00);
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_amount', 18, 2)->default(0.00);
            $table->decimal('tax', 18, 2)->default(0.00);
            $table->decimal('tax_amount', 18, 2)->default(0.00);
            $table->decimal('total', 18, 2)->default(0.00);
            $table->enum('status', ['draft', 'unpaid', 'partially_paid', 'paid', 'void'])->default('unpaid');
            $table->timestamp('due_date')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id']);
            $table->index(['business_subscription_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_invoices');
    }
};
