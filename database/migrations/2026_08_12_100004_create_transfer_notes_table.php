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
        Schema::create('transfer_notes', function (Blueprint $table) {
            $table->uuid('transfer_note_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->uuid('source_warehouse_id')->nullable();
            $table->uuid('destination_warehouse_id')->nullable();
            $table->string('transfer_note_no')->nullable();
            $table->timestamp('transfer_note_date')->nullable();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->decimal('total_quantity', 18, 3)->default(0.000);
            $table->decimal('total_value', 18, 3)->default(0.000);

            $table->enum('status', ['pending', 'approved', 'cancelled'])->default('pending');

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
        Schema::dropIfExists('transfer_notes');
    }
};
