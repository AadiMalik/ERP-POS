<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'package_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'package_id',
        'name',
        'description',
        'price',
        'order',
        'duration_type',
        'duration_days',
        'trial_days',
        'status',
        'max_branches',
        'max_users',
        'max_customers',
        'max_warehouses',
        'max_categories',
        'max_products',
        'max_suppliers',
        'max_purchase_orders',
        'max_purchases',
        'max_sales',
        'max_transfers',
        'max_expenses',
        'max_vouchers',
        'is_pos_enabled',
        'is_inventory_enabled',
        'is_accounting_enabled',
        'is_hrm_enabled',
        'is_payroll_enabled',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

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
