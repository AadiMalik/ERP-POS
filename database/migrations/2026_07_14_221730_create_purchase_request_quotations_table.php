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
        Schema::create('purchase_request_quotations', function (Blueprint $table) {
            $table->uuid('purchase_request_quotation_id')->primary();
            $table->uuid('purchase_request_id')->nullable();
            $table->uuid('supplier_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->uuid('business_id')->nullable();
            $table->string('purchase_request_quotation_no')->nullable();
            $table->timestamp('sent_date')->nullable();
            $table->timestamp('received_date')->nullable();
            $table->enum('status', [
                'sent',
                'received',
                'selected',
                'rejected',
            ])->default('sent');
            $table->string('vendor_reference_no')->nullable();
            $table->text('description')->nullable();
            $table->decimal('subtotal', 18, 3)->default(0.000);
            $table->decimal('discount', 18, 3)->default(0.000);
            $table->decimal('discount_amount', 18, 3)->default(0.000);
            $table->decimal('tax', 18, 3)->default(0.000);
            $table->decimal('tax_amount', 18, 3)->default(0.000);
            $table->decimal('other_charge', 18, 3)->default(0.000);
            $table->decimal('total', 18, 3)->default(0.000);
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
        Schema::dropIfExists('purchase_request_quotations');
    }
};
