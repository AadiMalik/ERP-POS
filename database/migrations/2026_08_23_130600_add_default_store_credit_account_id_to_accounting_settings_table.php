<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated store-credit liability account, separate from
 * default_customer_account_id (the AR account 'credit'-type/pay-on-account
 * payments and the credit-limit check both use) - so a customer's store
 * credit balance can never net against, or inflate, how much they're
 * allowed to buy on account. Phase 2 plan, batch E.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->uuid('default_store_credit_account_id')->nullable()->after('default_customer_account_id');
        });
    }

    public function down()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropColumn('default_store_credit_account_id');
        });
    }
};
