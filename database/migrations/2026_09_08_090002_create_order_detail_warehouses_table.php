<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Warehouse-split breakdown for a non-batch-tracked sale line that had to
     * be fulfilled from more than one of the branch's linked warehouses (a
     * batch-tracked line's per-warehouse breakdown already lives in
     * order_detail_batches, since a batch is warehouse-scoped 1:1). A line
     * fully covered by one warehouse gets no row here - order_details plus
     * the order's own warehouse_id already say enough.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_detail_warehouses', function (Blueprint $table) {
            $table->uuid('order_detail_warehouse_id')->primary();
            $table->uuid('order_detail_id');
            $table->uuid('warehouse_id');
            $table->decimal('quantity', 18, 3)->default(0.000);
            $table->decimal('base_quantity', 18, 3)->default(0.000);

            $table->uuid('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->index('order_detail_id');
            $table->index('warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_detail_warehouses');
    }
};
