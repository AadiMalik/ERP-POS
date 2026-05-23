<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FbrSetting extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'business_id',
        'enable_fbr',
        'fbr_environment',
        'fbr_pos_id',
        'fbr_license_key',
        'fbr_ntn',
        'fbr_strn',
        'fbr_sandbox_url',
        'fbr_production_url',

        'createdby_id',
        'updatedby_id',

        'date_created',
        'date_updated',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
