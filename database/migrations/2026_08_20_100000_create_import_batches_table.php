<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->uuid('import_batch_id')->primary();
            $table->uuid('business_id');
            $table->uuid('branch_id')->nullable();
            $table->string('module_key');
            $table->integer('uploaded_by_id');
            $table->string('original_filename')->nullable();
            $table->string('file_path');
            $table->enum('status', ['pending_preview', 'previewed', 'confirmed', 'failed', 'expired'])->default('pending_preview');
            $table->integer('row_count')->default(0);
            $table->integer('valid_count')->default(0);
            $table->integer('invalid_count')->default(0);
            $table->integer('create_count')->default(0);
            $table->integer('update_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->json('summary_json')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('is_deleted')->default(0);
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'module_key', 'status']);
            $table->index(['uploaded_by_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
