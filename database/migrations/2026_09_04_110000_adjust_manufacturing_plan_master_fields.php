<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manufacturing Plan master-table field set per business feedback: a plain
 * business-facing `plan_date` (mirrors purchase_date/order_date elsewhere -
 * distinct from the date_created audit timestamp), `is_complete` alongside
 * the more granular `status` enum, and `approvedby_id` (who approved/
 * confirmed the plan, alongside the existing confirmed_at timestamp).
 * planned_start_date/planned_end_date/notes are dropped - not part of the
 * requested field set.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('manufacturing_plans', function (Blueprint $table) {
            $table->date('plan_date')->nullable()->after('plan_no');
            $table->boolean('is_complete')->default(false)->after('status');
            $table->integer('approvedby_id')->nullable()->after('confirmed_at');
            $table->dropColumn(['planned_start_date', 'planned_end_date', 'notes']);
        });
    }

    public function down()
    {
        Schema::table('manufacturing_plans', function (Blueprint $table) {
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->text('notes')->nullable();
            $table->dropColumn(['plan_date', 'is_complete', 'approvedby_id']);
        });
    }
};
