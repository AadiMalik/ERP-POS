<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timezone extends Model
{
    use HasFactory;
    protected $primaryKey = 'timezone_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'timezone_id',
        'name',
        'offset',
    ];
}
