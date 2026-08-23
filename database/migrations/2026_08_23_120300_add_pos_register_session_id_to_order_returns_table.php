<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links an approved cash-refunded Order Return to the register session that
 * was open for the approving cashier at the time, so
 * PosRegisterSessionService::getSummary() can deduct actual cash refunds
 * from expected cash at shift closing instead of the previous hardcoded 0.
 * See Phase 1 plan's "Cash Refund -> Shift Reconciliation".
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('order_returns', function (Blueprint $table) {
            $table->string('pos_register_session_id', 36)->nullable()->after('refund_payment_method_id');
            $table->index('pos_register_session_id');
        });
    }

    public function down()
    {
        Schema::table('order_returns', function (Blueprint $table) {
            $table->dropIndex(['pos_register_session_id']);
            $table->dropColumn('pos_register_session_id');
        });
    }
};
