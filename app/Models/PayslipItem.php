<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayslipItem extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'payslip_item_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'payslip_item_id',
        'payslip_id',
        'component_name',
        'type',
        'amount',
    ];
}
