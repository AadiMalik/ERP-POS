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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->decimal('opening_balance', 18, 3)->default(0)->after('balance');
            $table->enum('opening_balance_type', ['Dr', 'Cr'])->nullable()->after('opening_balance');

            // Free-text term (e.g. "Net 30", "Due on Receipt"), distinct from the
            // numeric credit_days already used for credit-limit enforcement.
            // Note: the existing `description` column is reused as "notes" in the
            // UI, no separate notes column is added here.
            $table->string('payment_terms')->nullable()->after('opening_balance_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['opening_balance', 'opening_balance_type', 'payment_terms']);
        });
    }
};
