<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'purchase_order_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'purchase_order_id',
        'supplier_id',
        'branch_id',
        'business_id',
        'warehouse_id',
        'purchase_order_no',
        'purchase_order_date',
        'purchase_expected_date',
        'description',
        'subtotal',
        'discount',
        'discount_amount',
        'tax',
        'tax_amount',
        'shipping_charge',
        'total',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function purchaseOrderDetails() {
        return $this->hasMany(PurchaseOrderDetail::class, 'purchase_order_id', 'purchase_order_id');
    }

    public function business() {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function branch() {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function warehouse() {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function createdby() {
        return $this->belongsTo(User::class, 'createdby_id', 'user_id');
    }

    public function updatedby() {
        return $this->belongsTo(User::class, 'updatedby_id', 'user_id');
    }

    public function deletedby() {
        return $this->belongsTo(User::class, 'deletedby_id', 'user_id');
    }
}
