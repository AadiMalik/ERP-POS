<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteCart extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'cart_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'cart_id',
        'business_id',
        'user_id',
        'branch_id',
        'voucher_id',
        'voucher_code',
        'date_created',
        'date_updated',
    ];

    public function items()
    {
        return $this->hasMany(WebsiteCartItem::class, 'cart_id', 'cart_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
