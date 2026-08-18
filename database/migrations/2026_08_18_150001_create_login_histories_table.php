<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->uuid('login_history_id')->primary();

            // Nullable - a failed login attempt with an unknown/invalid
            // email has no matching user row.
            $table->integer('user_id')->nullable();
            $table->uuid('business_id')->nullable();
            $table->uuid('branch_id')->nullable();

            $table->string('email')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device')->nullable();

            $table->enum('status', ['success', 'failed'])->default('success');

            $table->timestamp('login_at')->nullable();
            $table->timestamp('logout_at')->nullable();

            $table->index(['user_id', 'login_at']);
            $table->index(['business_id', 'login_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('login_histories');
    }
};
