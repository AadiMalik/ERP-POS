<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user DataTable layout (visible columns, order, sort, page length,
     * export columns, last-used filters) and named Saved Views for the
     * centralized ERP DataTable engine. One preference row per user+table_key;
     * many named views per user+table_key. See
     * resources/docs/developer/24-datatable-system.md.
     */
    public function up()
    {
        Schema::create('datatable_preferences', function (Blueprint $table) {
            $table->uuid('datatable_preference_id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('business_id')->nullable();
            $table->string('table_key', 80);
            $table->json('visible_columns')->nullable();
            $table->json('column_order')->nullable();
            $table->string('sort_column', 80)->nullable();
            $table->string('sort_dir', 4)->nullable();
            $table->unsignedSmallInteger('page_length')->nullable();
            $table->json('export_columns')->nullable();
            $table->json('filters')->nullable();
            $table->uuid('active_view_id')->nullable();
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();

            $table->unique(['user_id', 'table_key'], 'dt_pref_user_table_unique');
            $table->index(['table_key']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('datatable_views', function (Blueprint $table) {
            $table->uuid('datatable_view_id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('business_id')->nullable();
            $table->string('table_key', 80);
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->json('visible_columns')->nullable();
            $table->json('column_order')->nullable();
            $table->string('sort_column', 80)->nullable();
            $table->string('sort_dir', 4)->nullable();
            $table->unsignedSmallInteger('page_length')->nullable();
            $table->json('export_columns')->nullable();
            $table->json('filters')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['user_id', 'table_key']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('datatable_views');
        Schema::dropIfExists('datatable_preferences');
    }
};
