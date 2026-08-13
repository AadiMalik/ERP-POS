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
        'register_mode',
        'open_time',
        'close_time',
        'invoice_prefix',
        'invoice_start',
        'invoice_footer',
        'default_customer_user_id',
        'default_payment_method_id',
        'default_order_type_id',
        'default_order_source_id',
        'enable_discount',
        'discount_level',
        'allow_backdated_sale',
        'backdated_sale_max_days',
        'daily_order_id_reset',
        'return_window_days',
        'require_return_reason',
        'allow_partial_return',
        'auto_print_invoice',
        'show_product_image',
        'enable_hold_order',

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
