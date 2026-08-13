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
        // Scope pivot - an absent row for a discount means it applies to all branches.
        Schema::create('discount_branches', function (Blueprint $table) {
            $table->id();
            $table->string('discount_id');
            $table->string('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discount_branches');
    }
};
