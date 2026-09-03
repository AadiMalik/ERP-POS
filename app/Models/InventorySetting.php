<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventorySetting extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'business_id',
        'stock_tracking',
        'negative_stock',
        'low_stock_alert',
        'low_stock_quantity',
        'barcode_type',
        'auto_generate_sku',
        'enable_batch_no',
        'enable_expiry_date',
        'block_expired_sale',
        'batch_selection_strategy',
        'near_expiry_days',
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
