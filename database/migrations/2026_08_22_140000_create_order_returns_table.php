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
        Schema::create('order_returns', function (Blueprint $table) {
            $table->uuid('order_return_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->uuid('warehouse_id')->nullable();
            // References users.id (unsignedBigInteger) - orders.user_id is the
            // same shape (see 2026_08_14_130004_add_user_id_to_orders_table.php),
            // not a uuid.
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->string('order_return_no')->nullable();
            $table->timestamp('order_return_date')->nullable();
            $table->decimal('subtotal', 18, 3)->default(0.000);
            $table->decimal('discount_amount', 18, 3)->default(0.000);
            $table->decimal('tax_amount', 18, 3)->default(0.000);
            $table->decimal('total', 18, 3)->default(0.000);
            $table->uuid('refund_payment_method_id')->nullable();
            $table->text('reason')->nullable();
            $table->text('description')->nullable();

            $table->enum('status', ['pending', 'approved', 'cancelled'])->default('pending');

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index('order_id');
            $table->index(['business_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_returns');
    }
};
