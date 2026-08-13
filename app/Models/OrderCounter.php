<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCounter extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'business_id',
        'branch_id',
        'counter_date',
        'last_number',
    ];
}
