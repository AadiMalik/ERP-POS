<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-business Google Login / Facebook Login / CAPTCHA configuration -
     * each business plugs in its own Google/Facebook/reCAPTCHA project and
     * toggles it on independently for its website + mobile app. Mirrors the
     * FirebaseSetting shape (one row per business, secrets encrypted).
     */
    public function up()
    {
        Schema::create('login_security_settings', function (Blueprint $table) {
            $table->uuid('login_security_setting_id')->primary();
            $table->uuid('business_id')->unique();

            $table->boolean('is_google_enabled')->default(false);
            $table->string('google_client_id')->nullable();
            $table->string('google_android_client_id')->nullable();
            $table->string('google_ios_client_id')->nullable();

            $table->boolean('is_facebook_enabled')->default(false);
            $table->string('facebook_app_id')->nullable();
            $table->text('facebook_app_secret')->nullable();

            $table->boolean('is_captcha_enabled')->default(false);
            $table->string('recaptcha_site_key')->nullable();
            $table->text('recaptcha_secret_key')->nullable();

            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('login_security_settings');
    }
};
