<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestQuotation extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'purchase_request_quotation_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'purchase_request_quotation_id',
        'purchase_request_id',
        'supplier_id',
        'branch_id',
        'business_id',
        'purchase_request_quotation_no',
        'sent_date',
        'received_date',
        'status',
        'vendor_reference_no',
        'description',
        'tax',
        'discount',
        'tax_amount',
        'discount_amount',
        'subtotal',
        'total',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function purchaseRequestQuotationDetails()
    {
        return $this->hasMany(PurchaseRequestQuotationDetail::class, 'purchase_request_quotation_id', 'purchase_request_quotation_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
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
