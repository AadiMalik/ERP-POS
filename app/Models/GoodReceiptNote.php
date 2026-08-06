<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodReceiptNote extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'good_receipt_note_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'good_receipt_note_id',
        'branch_id',
        'business_id',
        'supplier_id',
        'purchase_id',
        'warehouse_id',
        'good_receipt_note_no',
        'good_receipt_note_date',
        'description',
        'total',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function goodReceiptNoteDetails()
    {
        return $this->hasMany(GoodReceiptNoteDetail::class, 'good_receipt_note_id', 'good_receipt_note_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
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
