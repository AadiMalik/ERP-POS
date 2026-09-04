<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManufacturingPlan extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'manufacturing_plan_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'manufacturing_plan_id',
        'plan_no',
        'plan_date',
        'business_id',
        'branch_id',
        'product_id',
        'product_variation_id',
        'product_recipe_id',
        'planned_quantity',
        'planned_unit_id',
        'produced_quantity',
        'status',
        'is_complete',
        'confirmed_at',
        'approvedby_id',
        'cancelled_at',
        'cancel_reason',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function recipe()
    {
        return $this->belongsTo(ProductRecipe::class, 'product_recipe_id', 'product_recipe_id');
    }

    public function plannedUnit()
    {
        return $this->belongsTo(Unit::class, 'planned_unit_id');
    }

    public function materials()
    {
        return $this->hasMany(ManufacturingPlanMaterial::class, 'manufacturing_plan_id', 'manufacturing_plan_id');
    }

    public function productions()
    {
        return $this->hasMany(Production::class, 'manufacturing_plan_id', 'manufacturing_plan_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }

    public function approvedby()
    {
        return $this->belongsTo(User::class, 'approvedby_id');
    }

    /**
     * Planned quantity not yet covered by a completed production - a
     * production's quantity must never exceed this.
     */
    public function getRemainingQuantityAttribute()
    {
        return max(0, (float) $this->planned_quantity - (float) $this->produced_quantity);
    }

    public function getProgressPercentageAttribute()
    {
        $planned = (float) $this->planned_quantity;
        if ($planned <= 0) {
            return 0;
        }
        return round(min(100, ((float) $this->produced_quantity / $planned) * 100), 2);
    }
}
