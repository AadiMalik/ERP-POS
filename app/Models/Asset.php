<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'asset_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'asset_id',
        'asset_tag',
        'name',
        'category',
        'product_id',
        'purchase_date',
        'purchase_value',
        'condition',
        'status',
        'business_id',
        'branch_id',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function allocations()
    {
        return $this->hasMany(AssetAllocation::class, 'asset_id', 'asset_id');
    }

    public function currentAllocation()
    {
        return $this->hasOne(AssetAllocation::class, 'asset_id', 'asset_id')->where('status', 'issued');
    }
}
