<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Classifies how a `limited` module's usage is counted (see
     * FeatureLimitService::resolveCount()): active_count/configuration_count
     * are current live-row counts (branches, warehouses, accounts, ...);
     * monthly_creation/monthly_transaction count every row created within the
     * business's current billing period, including soft-deleted/cancelled
     * ones, and reset next period. "Unlimited" is not a value here — it's
     * already the existing `is_unlimited` flag.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('package_modules', function (Blueprint $table) {
            $table->enum('limit_type', ['monthly_creation', 'monthly_transaction', 'active_count', 'configuration_count'])
                ->nullable()
                ->after('is_unlimited');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('package_modules', function (Blueprint $table) {
            $table->dropColumn('limit_type');
        });
    }
};
