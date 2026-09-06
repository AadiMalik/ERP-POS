<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemFeatureFlag extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'system_feature_flag_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'system_feature_flag_id',
        'key',
        'label',
        'description',
        'category',
        'is_enabled',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];
}
