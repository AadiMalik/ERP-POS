<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteFaq extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'faq_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'faq_id',
        'business_id',
        'question',
        'answer',
        'sort_order',
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
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }
}
