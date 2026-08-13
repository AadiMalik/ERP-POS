<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'order_status_history';
    protected $primaryKey = 'order_status_history_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'order_status_history_id',
        'order_id',
        'from_status',
        'to_status',
        'reason',
        'changedby_id',
        'date_created',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function changedby()
    {
        return $this->belongsTo(User::class, 'changedby_id');
    }
}
