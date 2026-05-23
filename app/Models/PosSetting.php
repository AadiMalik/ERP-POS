<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosSetting extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'business_id',
        'invoice_prefix',
        'invoice_start',
        'invoice_footer',
        'default_customer_id',
        'default_payment_method_id',
        'enable_discount',
        'auto_print_invoice',
        'show_product_image',

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
