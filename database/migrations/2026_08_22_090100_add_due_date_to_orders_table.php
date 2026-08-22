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
        Schema::table('orders', function (Blueprint $table) {
            // Optional due date for Credit sales, captured via the POS
            // Credit Payment popup shown after a credit order completes
            // (pre-filled from the customer's credit_days). Purely
            // informational - does not affect JV generation, which already
            // happens in OrderService::post() regardless of this field.
            $table->date('due_date')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};
