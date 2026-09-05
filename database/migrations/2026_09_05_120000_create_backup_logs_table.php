<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->uuid('backup_log_id')->primary();
            $table->string('type', 20); // manual | scheduled | pre_restore
            $table->string('status', 20); // running | success | failed
            $table->string('disk', 50)->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->boolean('includes_database')->default(true);
            $table->boolean('includes_files')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('initiated_by')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->timestamp('date_created')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('backup_logs');
    }
};
