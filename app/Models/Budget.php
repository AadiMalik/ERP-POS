<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'budget_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'budget_id',
        'business_id',
        'fiscal_year_id',
        'name',
        'granularity',
        'generation_mode',
        'growth_percent',
        'status',
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

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id', 'fiscal_year_id');
    }

    public function budgetLines()
    {
        return $this->hasMany(BudgetLine::class, 'budget_id', 'budget_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updatedby_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deletedby_id');
    }
}
