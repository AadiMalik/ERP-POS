<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->uuid('subscriber_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->string('email');
            $table->string('source')->nullable();

            $table->enum('status', ['subscribed', 'unsubscribed'])->default('subscribed');
            $table->timestamp('unsubscribed_at')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
