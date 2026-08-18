<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('expense_categories', function (Blueprint $table) {
            // true (default) = this category's account_id follows
            // accounting_settings.default_expense_account_id and gets
            // re-synced whenever that setting changes. false = the business
            // admin manually picked a specific account on this category, so
            // it is excluded from that sync.
            $table->boolean('use_default_account')->default(true)->after('account_id');
        });

        // Backfill: any pre-existing category that already had a concrete
        // account_id was set that way through the old plain dropdown (no
        // "use default" concept existed yet) - treat that as a deliberate
        // manual choice so it isn't silently overwritten the next time the
        // Accounting Settings Expense Account changes. Only rows with no
        // account_id at all default to "follows the business default".
        DB::table('expense_categories')
            ->whereNotNull('account_id')
            ->update(['use_default_account' => false]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('use_default_account');
        });
    }
};
