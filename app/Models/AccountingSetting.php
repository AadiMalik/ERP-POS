<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingSetting extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'business_id',
        'enable_accounting',
        'default_cash_account_id',
        'default_bank_account_id',
        'default_discount_account_id',
        'default_tax_account_id',
        'default_revenue_account_id',
        'default_purchase_account_id',
        'default_expense_account_id',
        'default_supplier_account_id',
        'default_customer_account_id',
        'currency',
        'currency_symbol',
        'currency_position',
        'decimal_points',

        'createdby_id',
        'updatedby_id',

        'date_created',
        'date_updated',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
