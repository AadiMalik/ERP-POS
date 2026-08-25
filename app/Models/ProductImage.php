<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'product_image_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'product_image_id',
        'product_id',
        'image',
        'sorting',
        'is_default',
        'status',
        'createdby_id',
        'date_created',
    ];
    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return !empty($this->image)
            ? asset('uploads/product/' . $this->image)
            : asset('assets/img/no-image.png'); // optional default image
    }
    public function product() {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
