<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferNote extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'transfer_note_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'transfer_note_id',
        'business_id',
        'branch_id',
        'source_warehouse_id',
        'destination_warehouse_id',
        'destination_branch_id',
        'transfer_note_no',
        'transfer_note_date',
        'reference',
        'description',
        'total_quantity',
        'total_value',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'sentby_id',
        'receivedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
        'date_sent',
        'date_received',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function destinationBranch()
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function transferNoteDetails()
    {
        return $this->hasMany(TransferNoteDetail::class, 'transfer_note_id', 'transfer_note_id');
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

    public function sentby()
    {
        return $this->belongsTo(User::class, 'sentby_id');
    }

    public function receivedby()
    {
        return $this->belongsTo(User::class, 'receivedby_id');
    }
}
