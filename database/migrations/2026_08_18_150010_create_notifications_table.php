<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The event/content of an in-app alert (Phase 2 of the Notifications,
     * Automation, Audit & Security system - see
     * 2026_08_18_150002_seed_audit_security_permissions.php). Read state is
     * tracked separately per recipient in notification_recipients, since one
     * event (e.g. subscription expiring) can reach both a Business Admin and
     * a Super Admin with independent unread badges.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('notification_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->uuid('branch_id')->nullable();

            $table->string('type');
            $table->string('title');
            $table->text('message');

            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('url')->nullable();
            $table->json('data')->nullable();

            // Duplicate-prevention key for the same event/alert period - see
            // the unique index below. A day-string for periodic threshold
            // checks (low stock, payment due, credit limit, supplier
            // reminder), a fixed marker for subscription expiry (fires once
            // per subscription), or the new status value for order-status
            // changes (fires once per transition).
            $table->string('dedupe_key');

            $table->timestamp('date_created')->nullable();

            $table->unique(['type', 'reference_type', 'reference_id', 'dedupe_key'], 'notifications_dedupe_unique');
            $table->index(['business_id', 'date_created']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
