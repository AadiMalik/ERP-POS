<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'review_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'review_id',
        'business_id',
        'product_id',
        'customer_id',
        'reviewer_name',
        'rating',
        'comment',
        'status',
        'is_deleted',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }
}
