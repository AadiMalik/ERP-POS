<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionConsumption extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'production_consumption_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'production_consumption_id',
        'production_id',
        'product_id',
        'product_variation_id',
        'product_variation_batch_id',
        'warehouse_id',
        'base_quantity',
        'unit_cost',
        'total_cost',
        'product_variation_stock_transaction_id',
        'date_created',
    ];

    public function production()
    {
        return $this->belongsTo(Production::class, 'production_id', 'production_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function batch()
    {
        return $this->belongsTo(ProductVariationBatch::class, 'product_variation_batch_id', 'product_variation_batch_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function stockTransaction()
    {
        return $this->belongsTo(ProductVariationStockTransaction::class, 'product_variation_stock_transaction_id', 'product_variation_stock_transaction_id');
    }
}
