<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'purchase_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'purchase_id',
        'supplier_id',
        'branch_id',
        'business_id',
        'warehouse_id',
        'purchase_request_id',
        'purchase_no',
        'purchase_date',
        'expected_delivery_date',
        'purchase_type',
        'subtotal',
        'discount',
        'discount_amount',
        'tax',
        'tax_amount',
        'shipping_charge',
        'total',
        'payment_status',
        'description',
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

    public function purchaseRequest() {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id', 'purchase_request_id');
    }

    public function purchaseDetails() {
        return $this->hasMany(PurchaseDetail::class, 'purchase_id', 'purchase_id');
    }

    public function warehouse() {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function branch() {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function business() {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function createdby() {
        return $this->belongsTo(User::class, 'createdby_id');
    }

    public function updatedby() {
        return $this->belongsTo(User::class, 'updatedby_id');
    }

    public function deletedby() {
        return $this->belongsTo(User::class, 'deletedby_id');
    }
}
