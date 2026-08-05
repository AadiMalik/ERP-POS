<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'purchase_request_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'purchase_request_id',
        'supplier_id',
        'branch_id',
        'business_id',
        'warehouse_id',
        'department_id',
        'purchase_request_no',
        'purchase_request_date',
        'purchase_expected_date',
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
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function purchaseRequestDetails()
    {
        return $this->hasMany(PurchaseRequestDetail::class, 'purchase_request_id', 'purchase_request_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
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
