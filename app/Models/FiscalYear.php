<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiscalYear extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'fiscal_year_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'fiscal_year_id',
        'business_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'is_current',
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

    public function accountingPeriods()
    {
        return $this->hasMany(AccountingPeriod::class, 'fiscal_year_id', 'fiscal_year_id');
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class, 'fiscal_year_id', 'fiscal_year_id');
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
