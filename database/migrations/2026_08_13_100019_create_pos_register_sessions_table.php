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
        Schema::create('pos_register_sessions', function (Blueprint $table) {
            $table->uuid('pos_register_session_id')->primary();
            $table->string('pos_register_id');
            $table->string('business_id');
            $table->string('branch_id');
            $table->integer('cashier_id');
            $table->timestamp('opening_datetime');
            $table->decimal('opening_cash', 18, 3);
            $table->text('opening_notes')->nullable();
            $table->timestamp('closing_datetime')->nullable();
            $table->decimal('expected_cash', 18, 3)->nullable();
            $table->decimal('actual_cash', 18, 3)->nullable();
            $table->decimal('cash_difference', 18, 3)->nullable();
            $table->text('closing_notes')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');

            // Posted (closed) sessions are never hard-deleted - voiding one requires
            // the same reversal/permission mechanism as orders.
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
        Schema::dropIfExists('pos_register_sessions');
    }
};
