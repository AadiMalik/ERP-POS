<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tags the debit leg of a card/bank sale payment with the specific Bank
     * used, alongside the account_id it already posts to - lets the ledger
     * distinguish which bank an amount landed in even when several Banks
     * share one Chart-of-Accounts account.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('journal_entry_details', function (Blueprint $table) {
            $table->uuid('bank_id')->nullable()->after('account_id');
            $table->index('bank_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('journal_entry_details', function (Blueprint $table) {
            $table->dropIndex(['bank_id']);
            $table->dropColumn('bank_id');
        });
    }
};
