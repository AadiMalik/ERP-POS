<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManufacturingPlanMaterial extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'manufacturing_plan_material_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'manufacturing_plan_material_id',
        'manufacturing_plan_id',
        'product_id',
        'product_variation_id',
        'unit_id',
        'warehouse_id',
        'required_base_quantity',
        'reserved_quantity',
        'consumed_quantity',
        'date_created',
        'date_updated',
    ];

    public function plan()
    {
        return $this->belongsTo(ManufacturingPlan::class, 'manufacturing_plan_id', 'manufacturing_plan_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function getOutstandingReservedQuantityAttribute()
    {
        return max(0, (float) $this->reserved_quantity - (float) $this->consumed_quantity);
    }
}
