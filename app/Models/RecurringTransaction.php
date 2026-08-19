<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringTransaction extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'recurring_transaction_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $casts = [
        'template_data'   => 'array',
        'start_date'      => 'date',
        'end_date'        => 'date',
        'next_run_date'   => 'date',
        'last_run_date'   => 'date',
        'auto_post'       => 'boolean',
        'is_deleted'      => 'boolean',
    ];
    protected $fillable = [
        'recurring_transaction_id',
        'business_id',
        'branch_id',
        'transaction_type',
        'name',
        'frequency',
        'weekday',
        'day_of_month',
        'month_of_year',
        'start_date',
        'end_date',
        'max_occurrences',
        'occurrences_count',
        'next_run_date',
        'last_run_date',
        'status',
        'auto_post',
        'notes',
        'template_data',

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

    public function runs()
    {
        return $this->hasMany(RecurringTransactionRun::class, 'recurring_transaction_id', 'recurring_transaction_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }

    public function updatedby()
    {
        return $this->belongsTo(User::class, 'updatedby_id');
    }

    public function deletedby()
    {
        return $this->belongsTo(User::class, 'deletedby_id');
    }
}
