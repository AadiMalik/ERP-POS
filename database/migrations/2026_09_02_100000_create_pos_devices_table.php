<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_devices', function (Blueprint $table) {
            $table->string('pos_device_id', 36)->primary();
            $table->string('business_id', 36);
            $table->string('branch_id', 36)->nullable();
            $table->string('warehouse_id', 36)->nullable();
            $table->string('pos_register_id', 36)->nullable();
            $table->string('name');
            $table->string('device_fingerprint', 128)->nullable();
            $table->string('api_token_hash', 128);
            $table->string('status', 20)->default('active');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->json('sync_cursors')->nullable();
            $table->string('createdby_id', 36)->nullable();
            $table->string('updatedby_id', 36)->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->tinyInteger('is_deleted')->default(0);

            $table->index(['business_id', 'status']);
            $table->index('device_fingerprint');
        });

        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'pos_device_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('pos_device_id', 36)->nullable()->after('register_session_id');
                $table->string('offline_local_id', 64)->nullable()->after('pos_device_id');
                $table->index(['business_id', 'offline_local_id'], 'orders_business_offline_local_idx');
            });
        }

        if (Schema::hasTable('pos_register_sessions') && !Schema::hasColumn('pos_register_sessions', 'pos_device_id')) {
            Schema::table('pos_register_sessions', function (Blueprint $table) {
                $table->string('pos_device_id', 36)->nullable()->after('cashier_id');
                $table->string('offline_local_id', 64)->nullable()->after('pos_device_id');
            });
        }

        if (Schema::hasTable('pos_register_cash_movements') && !Schema::hasColumn('pos_register_cash_movements', 'offline_local_id')) {
            Schema::table('pos_register_cash_movements', function (Blueprint $table) {
                $table->string('offline_local_id', 64)->nullable()->after('pos_register_session_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pos_register_cash_movements') && Schema::hasColumn('pos_register_cash_movements', 'offline_local_id')) {
            Schema::table('pos_register_cash_movements', function (Blueprint $table) {
                $table->dropColumn('offline_local_id');
            });
        }

        if (Schema::hasTable('pos_register_sessions') && Schema::hasColumn('pos_register_sessions', 'offline_local_id')) {
            Schema::table('pos_register_sessions', function (Blueprint $table) {
                $table->dropColumn(['offline_local_id', 'pos_device_id']);
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'offline_local_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('orders_business_offline_local_idx');
                $table->dropColumn(['offline_local_id', 'pos_device_id']);
            });
        }

        Schema::dropIfExists('pos_devices');
    }
};
