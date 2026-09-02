<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency key for expenses pushed from the offline desktop POS - same
 * pattern as pos_register_sessions/pos_register_cash_movements.offline_local_id
 * (see 2026_09_02_100000_create_pos_devices_table.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expenses') && !Schema::hasColumn('expenses', 'offline_local_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->string('offline_local_id', 64)->nullable()->after('pos_register_session_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'offline_local_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn('offline_local_id');
            });
        }
    }
};
