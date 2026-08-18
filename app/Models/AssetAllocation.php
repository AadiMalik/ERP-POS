<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAllocation extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'asset_allocation_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'asset_allocation_id',
        'asset_id',
        'employee_id',
        'issue_date',
        'expected_return_date',
        'return_date',
        'condition_on_issue',
        'condition_on_return',
        'status',
        'remarks',
        'business_id',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id', 'asset_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
