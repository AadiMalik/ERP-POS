<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'production_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'production_id',
        'production_no',
        'business_id',
        'branch_id',
        'manufacturing_plan_id',
        'product_recipe_id',
        'warehouse_id',
        'quantity',
        'unit_id',
        'batch_no',
        'manufacturing_date',
        'expiry_date',
        'status',
        'operator_user_id',
        'notes',
        'material_cost',
        'labor_cost',
        'overhead_cost',
        'other_cost',
        'total_cost',
        'unit_cost',
        'completed_at',
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

    public function plan()
    {
        return $this->belongsTo(ManufacturingPlan::class, 'manufacturing_plan_id', 'manufacturing_plan_id');
    }

    public function recipe()
    {
        return $this->belongsTo(ProductRecipe::class, 'product_recipe_id', 'product_recipe_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }

    public function consumptions()
    {
        return $this->hasMany(ProductionConsumption::class, 'production_id', 'production_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
