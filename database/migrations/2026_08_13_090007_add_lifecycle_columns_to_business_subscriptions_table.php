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
        Schema::table('business_subscriptions', function (Blueprint $table) {
            $table->enum('billing_cycle', ['monthly', 'yearly'])->nullable()->after('package_id');
            $table->timestamp('grace_period_ends_at')->nullable()->after('end_at');
            $table->timestamp('cancelled_at')->nullable()->after('status');
            $table->string('renewed_from_subscription_id')->nullable()->after('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['billing_cycle', 'grace_period_ends_at', 'cancelled_at', 'renewed_from_subscription_id']);
        });
    }
};
