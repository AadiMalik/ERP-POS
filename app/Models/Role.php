<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory;
    protected $fillable = [
        'name',
        'guard_name',
        'business_id',
        'description'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class,'business_id');
    }
}
