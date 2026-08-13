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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('order_id')->primary();
            $table->unsignedInteger('daily_order_id');

            $table->string('business_id');
            $table->string('branch_id');
            $table->string('warehouse_id');

            // POS-only - nullable so Website/Mobile App/API orders (no register,
            // no cashier shift) can share this same table.
            $table->string('register_id')->nullable();
            $table->string('register_session_id')->nullable();
            $table->integer('cashier_id')->nullable();

            $table->string('customer_id')->nullable();
            $table->string('order_type_id')->nullable();
            // identifies the originating channel - POS, Website, Mobile App, API, ...
            $table->string('order_source_id')->nullable();

            // System creation timestamp - always "now" at creation, immutable.
            $table->timestamp('order_date');
            // Editable business date the sale is recorded against - defaults to
            // order_date, only backdatable per pos_settings.allow_backdated_sale.
            $table->date('sale_date');

            $table->decimal('subtotal', 18, 3)->default(0);
            $table->decimal('discount', 18, 3)->default(0);
            $table->decimal('discount_amount', 18, 3)->default(0);
            $table->decimal('tax', 18, 3)->default(0);
            $table->decimal('tax_amount', 18, 3)->default(0);
            $table->decimal('total', 18, 3)->default(0);
            // Sum of order_payments.amount actually collected/allocated - may exceed
            // total for a cash sale (see change_amount).
            $table->decimal('paid_amount', 18, 3)->default(0);
            // Cash handed back to the customer when paid_amount > total. Never posted
            // to any revenue account - it is a cash-drawer movement only.
            $table->decimal('change_amount', 18, 3)->default(0);

            $table->string('discount_id')->nullable();
            $table->string('voucher_id')->nullable();

            $table->text('notes')->nullable();

            $table->enum('status', ['draft', 'hold', 'posted', 'cancelled', 'void', 'returned'])->default('draft');

            $table->string('fbr_invoice_number')->nullable();
            $table->string('fbr_status')->nullable();
            $table->string('pra_invoice_number')->nullable();
            $table->string('pra_status')->nullable();

            $table->boolean('is_deleted')->default(0);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'branch_id', 'sale_date']);
            $table->index(['business_id', 'daily_order_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
