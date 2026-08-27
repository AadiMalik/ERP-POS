<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->uuid('notification_template_id')->primary();
            $table->uuid('business_id')->index();
            $table->string('name');
            $table->string('title');
            $table->text('body');
            $table->string('image')->nullable();
            $table->json('data')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'status', 'is_deleted'], 'notification_templates_biz_status_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_templates');
    }
};
