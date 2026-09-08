<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Explicit branch<->warehouse links, replacing the old "one warehouse
     * belongs to at most one branch" model. A branch's sellable stock is the
     * combined stock of every warehouse linked here (priority breaks ties
     * when FEFO consumption needs to pick between warehouses whose batches
     * have identical expiry/date).
     *
     * @return void
     */
    public function up()
    {
        Schema::create('branch_warehouses', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->uuid('warehouse_id');
            $table->integer('priority')->default(0);
            $table->timestamp('date_created')->nullable();

            $table->unique(['branch_id', 'warehouse_id']);
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
        Schema::dropIfExists('branch_warehouses');
    }
};
