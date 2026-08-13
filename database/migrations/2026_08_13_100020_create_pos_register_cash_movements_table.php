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
        Schema::create('pos_register_cash_movements', function (Blueprint $table) {
            $table->uuid('pos_register_cash_movement_id')->primary();
            $table->string('pos_register_session_id');
            $table->enum('type', ['in', 'out']);
            $table->decimal('amount', 18, 3);
            $table->string('reason')->nullable();

            // Append-only till-adjustment entries - a mistaken entry gets reversed by
            // a new opposite-direction entry, not edited, so no update/delete audit
            // columns are needed.
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pos_register_cash_movements');
    }
};
