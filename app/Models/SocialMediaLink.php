<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialMediaLink extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'social_media_link_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'social_media_link_id',
        'business_id',
        'platform',
        'url',
        'icon',
        'icon_color',
        'display_color',
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
