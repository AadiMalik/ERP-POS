<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'category_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'category_id',
        'business_id',
        'name',
        'logo',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        return !empty($this->logo)
            ? asset('public/uploads/category/' . $this->logo)
            : asset('public/assets/img/no-image.png'); // optional default image
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class, 'category_id', 'category_id');
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
