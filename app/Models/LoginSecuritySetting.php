<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginSecuritySetting extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'login_security_setting_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'login_security_setting_id',
        'business_id',
        'is_google_enabled',
        'google_client_id',
        'google_android_client_id',
        'google_ios_client_id',
        'is_facebook_enabled',
        'facebook_app_id',
        'facebook_app_secret',
        'is_captcha_enabled',
        'recaptcha_site_key',
        'recaptcha_secret_key',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    protected $casts = [
        'is_google_enabled' => 'boolean',
        'is_facebook_enabled' => 'boolean',
        'is_captcha_enabled' => 'boolean',
        'facebook_app_secret' => 'encrypted',
        'recaptcha_secret_key' => 'encrypted',
        'date_created' => 'datetime',
        'date_updated' => 'datetime',
    ];

    protected $hidden = ['facebook_app_secret', 'recaptcha_secret_key'];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function hasFacebookAppSecret(): bool
    {
        return filled($this->attributes['facebook_app_secret'] ?? null);
    }

    public function hasRecaptchaSecretKey(): bool
    {
        return filled($this->attributes['recaptcha_secret_key'] ?? null);
    }
}
