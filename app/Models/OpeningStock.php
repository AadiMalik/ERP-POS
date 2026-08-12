<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpeningStock extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'opening_stock_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'opening_stock_id',
        'business_id',
        'branch_id',
        'warehouse_id',
        'opening_stock_no',
        'opening_stock_date',
        'reference',
        'description',
        'total_quantity',
        'total_value',
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

    public function openingStockDetails()
    {
        return $this->hasMany(OpeningStockDetail::class, 'opening_stock_id', 'opening_stock_id');
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
