<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringTransactionRun extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'recurring_transaction_run_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $casts = [
        'run_date'      => 'date',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];
    protected $fillable = [
        'recurring_transaction_run_id',
        'recurring_transaction_id',
        'run_date',
        'status',
        'generated_model_type',
        'generated_model_id',
        'error_message',
        'triggered_by',
        'triggered_by_user_id',
        'started_at',
        'completed_at',
        'createdby_id',
        'date_created',
    ];

    public function recurringTransaction()
    {
        return $this->belongsTo(RecurringTransaction::class, 'recurring_transaction_id', 'recurring_transaction_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
