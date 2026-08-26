<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteBenefit extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'benefit_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'benefit_id',
        'business_id',
        'group',
        'title',
        'description',
        'value',
        'code',
        'icon',
        'icon_color',
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
