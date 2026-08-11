<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'purchase_return_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'purchase_return_id',
        'business_id',
        'branch_id',
        'supplier_id',
        'warehouse_id',
        'purchase_id',
        'good_receipt_note_id',
        'return_type',
        'purchase_return_no',
        'purchase_return_date',
        'subtotal',
        'discount',
        'discount_amount',
        'tax',
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

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function goodReceiptNote()
    {
        return $this->belongsTo(GoodReceiptNote::class, 'good_receipt_note_id');
    }

    public function purchaseReturnDetails()
    {
        return $this->hasMany(PurchaseReturnDetail::class, 'purchase_return_id', 'purchase_return_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
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
