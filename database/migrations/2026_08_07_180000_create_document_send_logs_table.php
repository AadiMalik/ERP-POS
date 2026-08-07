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
        Schema::create('document_send_logs', function (Blueprint $table) {
            $table->uuid('document_send_log_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->string('module');
            $table->uuid('record_id');
            $table->enum('channel', ['print', 'email', 'whatsapp', 'sms']);
            $table->string('recipient')->nullable();
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->text('message')->nullable();

            $table->integer('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->index(['module', 'record_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_send_logs');
    }
};
