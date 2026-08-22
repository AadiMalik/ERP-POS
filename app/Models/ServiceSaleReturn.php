<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSaleReturn extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'service_sale_return_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'service_sale_return_id',
        'business_id',
        'branch_id',
        'customer_id',
        'service_sale_id',
        'service_sale_return_no',
        'service_sale_return_date',
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

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }

    public function serviceSale()
    {
        return $this->belongsTo(ServiceSale::class, 'service_sale_id');
    }

    public function serviceSaleReturnDetails()
    {
        return $this->hasMany(ServiceSaleReturnDetail::class, 'service_sale_return_id', 'service_sale_return_id');
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
