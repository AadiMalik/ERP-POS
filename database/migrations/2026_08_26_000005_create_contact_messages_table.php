<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->uuid('contact_message_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');

            $table->enum('status', ['unread', 'read', 'replied'])->default('unread');
            $table->text('reply_message')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->integer('repliedby_id')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
