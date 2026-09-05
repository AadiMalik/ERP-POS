<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        Schema::create('backup_settings', function (Blueprint $table) {
            $table->uuid('backup_setting_id')->primary();
            $table->boolean('is_scheduled_enabled')->default(false);
            $table->string('frequency', 20)->default('daily'); // daily | weekly | monthly
            $table->string('run_time', 5)->default('02:00'); // HH:MM, 24h
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 0 (Sun) - 6 (Sat), for weekly
            $table->unsignedTinyInteger('day_of_month')->nullable(); // 1-28, for monthly
            $table->unsignedInteger('retention_days')->default(30);
            $table->unsignedInteger('max_storage_mb')->nullable();
            $table->json('disks')->nullable(); // e.g. ["backups"] or ["backups","s3"]
            $table->timestamp('last_run_at')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
        });

        DB::table('backup_settings')->insert([
            'backup_setting_id' => (string) Str::uuid(),
            'is_scheduled_enabled' => false,
            'frequency' => 'daily',
            'run_time' => '02:00',
            'retention_days' => 30,
            'disks' => json_encode(['backups']),
            'is_deleted' => 0,
            'date_created' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('backup_settings');
    }
};
