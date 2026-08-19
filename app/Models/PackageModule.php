<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'module_key',
        'is_enabled',
        'is_unlimited',
        'limit_value',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_unlimited' => 'boolean',
        'limit_value' => 'integer',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }
}
