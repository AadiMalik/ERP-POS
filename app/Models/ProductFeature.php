<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFeature extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'product_feature_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'product_feature_id',
        'product_id',
        'name',
        'description',
        'sorting',
        'status',
        'createdby_id',
        'date_created'
    ];

    public function product() {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function createdby() {
        return $this->belongsTo(User::class, 'createdby_id', 'user_id');
    }
}
