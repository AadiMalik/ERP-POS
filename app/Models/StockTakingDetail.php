<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTakingDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'stock_taking_detail_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'stock_taking_detail_id',
        'stock_taking_id',
        'product_id',
        'product_variation_id',
        'unit_id',
        'system_quantity',
        'physical_quantity',
        'difference_quantity',
        'unit_cost',
        'difference_value',
        'reason',
        'description',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    public function stockTaking()
    {
        return $this->belongsTo(StockTaking::class, 'stock_taking_id', 'stock_taking_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id', 'product_variation_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'unit_id');
    }
}
