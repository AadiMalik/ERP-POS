<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherRedemption extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'voucher_redemption_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'voucher_redemption_id',
        'voucher_id',
        'order_id',
        'user_id',
        'discount_amount',
        'is_deleted',
        'date_created',
        'createdby_id',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
