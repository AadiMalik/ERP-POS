<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntroTestimonial extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'intro_testimonials';
    protected $primaryKey = 'intro_testimonial_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'business_name',
        'customer_name',
        'designation',
        'business_type',
        'review_text',
        'rating',
        'image',
        'display_order',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted'
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return !empty($this->image) ? asset('public/uploads/intro/testimonials/' . $this->image) : null;
    }
}
