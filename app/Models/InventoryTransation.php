<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransation extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'inventory_transaction_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'inventory_transaction_id',
        'business_id',
        'branch_id',
        'warehouse_id',
        'product_id',
        'product_variation_id',
        'type',
        'quantity',
        'unit_cost',
        'notes',
        'transaction_date',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_updated',
        'date_created',
        'date_deleted'
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

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
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
