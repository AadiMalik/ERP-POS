<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'expense_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'expense_id',
        'recurring_transaction_id',
        'recurring_transaction_run_id',
        'business_id',
        'branch_id',
        'expense_category_id',
        'pos_register_session_id',
        'user_id',

        'expense_no',
        'expense_date',
        'amount',

        'payment_method',
        'payment_account_id',
        'expense_account_id',

        'reference_no',
        'description',
        'attachment',

        'source',
        'status',
        'postedby_id',
        'date_posted',
        'offline_local_id',

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

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id', 'expense_category_id');
    }

    public function posSession()
    {
        return $this->belongsTo(PosRegisterSession::class, 'pos_register_session_id', 'pos_register_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paymentAccount()
    {
        return $this->belongsTo(Account::class, 'payment_account_id', 'account_id');
    }

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id', 'account_id');
    }

    public function postedby()
    {
        return $this->belongsTo(User::class, 'postedby_id');
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
