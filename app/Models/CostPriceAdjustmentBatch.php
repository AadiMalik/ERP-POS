<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostPriceAdjustmentBatch extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'cost_price_adjustment_batch_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'cost_price_adjustment_batch_id',
        'cost_price_adjustment_id',
        'product_variation_batch_id',
        'batch_no',

        'quantity',
        'previous_batch_avg_price',
        'new_batch_avg_price',
        'batch_adjustment_amount',

        'date_created',
    ];

    public function costPriceAdjustment()
    {
        return $this->belongsTo(CostPriceAdjustment::class, 'cost_price_adjustment_id', 'cost_price_adjustment_id');
    }

    public function productVariationBatch()
    {
        return $this->belongsTo(ProductVariationBatch::class, 'product_variation_batch_id', 'product_variation_batch_id');
    }
}
