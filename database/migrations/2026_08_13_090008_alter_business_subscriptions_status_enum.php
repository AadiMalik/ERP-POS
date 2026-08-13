<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Widens the status enum to support the fuller subscription lifecycle
     * (trial, payment_pending, grace_period, cancelled) alongside the
     * existing active/suspended/expired values. Existing rows keep their
     * exact current value and meaning.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE business_subscriptions MODIFY status ENUM('active','suspended','expired','trial','payment_pending','grace_period','cancelled') NOT NULL DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     *
     * Only safe if no row currently uses one of the newly added values.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE business_subscriptions MODIFY status ENUM('active','suspended','expired') NOT NULL DEFAULT 'active'");
    }
};
