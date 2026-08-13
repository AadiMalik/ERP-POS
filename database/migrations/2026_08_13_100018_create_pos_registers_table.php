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
        Schema::create('pos_registers', function (Blueprint $table) {
            $table->uuid('pos_register_id')->primary();
            $table->string('business_id');
            $table->string('branch_id');
            $table->string('warehouse_id'); // fixes which stock this register sells from
            $table->string('name');
            $table->string('code');
            $table->integer('assigned_user_id')->nullable(); // manual mode locks the register to one user
            $table->enum('mode', ['manual', 'automatic'])->default('manual');
            $table->enum('status', ['active', 'inactive'])->default('active');

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
        Schema::dropIfExists('pos_registers');
    }
};
