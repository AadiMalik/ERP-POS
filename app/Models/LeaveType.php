<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'leave_type_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'leave_type_id',
        'name',
        'code',
        'is_paid',
        'max_days_per_year',
        'business_id',
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

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'leave_type_id', 'leave_type_id');
    }
}
