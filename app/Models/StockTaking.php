<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTaking extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'stock_taking_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'stock_taking_id',
        'business_id',
        'branch_id',
        'warehouse_id',
        'stock_taking_no',
        'stock_taking_date',
        'reference',
        'description',
        'total_difference_quantity',
        'total_difference_value',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function stockTakingDetails()
    {
        return $this->hasMany(StockTakingDetail::class, 'stock_taking_id', 'stock_taking_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }

    public function updatedby()
    {
        return $this->belongsTo(User::class, 'updatedby_id');
    }

    public function deletedby()
    {
        return $this->belongsTo(User::class, 'deletedby_id');
    }
}
