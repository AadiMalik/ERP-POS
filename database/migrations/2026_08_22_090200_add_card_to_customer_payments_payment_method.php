<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Adds 'card' to the payment_method enum (Credit Order Payment feature -
        // Receive Payment supports Cash/Card, mirroring the POS payment_methods.type
        // vocabulary). Same raw-SQL enum-alter pattern as
        // 2026_08_13_090030_extend_pos_settings_table.php.
        DB::statement("ALTER TABLE customer_payments MODIFY payment_method ENUM('cash','bank_transfer','cheque','online','card') DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE customer_payments MODIFY payment_method ENUM('cash','bank_transfer','cheque','online') DEFAULT 'cash'");
    }
};
