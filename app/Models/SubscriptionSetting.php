<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionSetting extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'subscription_setting_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'subscription_setting_id',
        'default_grace_period_days',
        'reminder_thresholds_days',
        'restrict_access_in_grace_period',
        'restricted_route_names',
        'invoice_prefix',

        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    protected $casts = [
        'reminder_thresholds_days' => 'array',
        'restricted_route_names' => 'array',
        'restrict_access_in_grace_period' => 'boolean',
    ];

    public static function current(): self
    {
        return static::where('is_deleted', 0)->first() ?? new static([
            'default_grace_period_days' => 7,
            'reminder_thresholds_days' => [30, 15, 7, 3, 1],
            'restrict_access_in_grace_period' => true,
            'restricted_route_names' => [],
            'invoice_prefix' => 'SUB-INV',
        ]);
    }
}
