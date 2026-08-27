<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business-wise Firebase service-account configuration for FCM HTTP v1.
     * One row per business (unique business_id). private_key is stored encrypted
     * via the model cast.
     */
    public function up()
    {
        Schema::create('firebase_settings', function (Blueprint $table) {
            $table->uuid('firebase_setting_id')->primary();
            $table->uuid('business_id')->unique();
            $table->string('project_id')->nullable();
            $table->string('client_email')->nullable();
            $table->text('private_key')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('firebase_settings');
    }
};
