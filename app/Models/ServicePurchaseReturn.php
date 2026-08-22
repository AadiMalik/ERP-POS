<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePurchaseReturn extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'service_purchase_return_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'service_purchase_return_id',
        'business_id',
        'branch_id',
        'supplier_id',
        'service_purchase_id',
        'service_purchase_return_no',
        'service_purchase_return_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'reason',
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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function servicePurchase()
    {
        return $this->belongsTo(ServicePurchase::class, 'service_purchase_id');
    }

    public function servicePurchaseReturnDetails()
    {
        return $this->hasMany(ServicePurchaseReturnDetail::class, 'service_purchase_return_id', 'service_purchase_return_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
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
