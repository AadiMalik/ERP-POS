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
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->enum('aging_basis', ['due_date', 'invoice_date'])->default('due_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropColumn('aging_basis');
        });
    }
};
