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
        Schema::create('good_receipt_notes', function (Blueprint $table) {
            $table->uuid('good_receipt_note_id')->primary();
            $table->uuid('branch_id')->nullable();
            $table->uuid('business_id')->nullable();
            $table->uuid('purchase_id')->nullable();
            $table->uuid('warehouse_id')->nullable();
            $table->string('good_receipt_note_no')->nullable();
            $table->timestamp('good_receipt_note_date')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'received'])->default('received');

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
        Schema::dropIfExists('good_receipt_notes');
    }
};
