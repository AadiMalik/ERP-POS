<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteTestimonial extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'testimonial_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'testimonial_id',
        'business_id',
        'author_name',
        'author_title',
        'avatar',
        'quote',
        'rating',
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

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute()
    {
        return !empty($this->avatar) ? asset('public/uploads/website/testimonial/' . $this->avatar) : null;
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }
}
