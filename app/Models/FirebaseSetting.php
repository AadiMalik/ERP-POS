<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FirebaseSetting extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'firebase_setting_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'firebase_setting_id',
        'business_id',
        'project_id',
        'client_email',
        'private_key',
        'is_active',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        // Encrypt service-account private key at rest (APP_KEY).
        'private_key' => 'encrypted',
        'date_created' => 'datetime',
        'date_updated' => 'datetime',
    ];

    protected $hidden = [
        'private_key',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }

    /**
     * True when the business has an active row with all required FCM fields.
     */
    public function isConfigured(): bool
    {
        return (bool) $this->is_active
            && filled($this->project_id)
            && filled($this->client_email)
            && filled($this->private_key);
    }

    public function hasPrivateKey(): bool
    {
        return filled($this->attributes['private_key'] ?? null);
    }
}
