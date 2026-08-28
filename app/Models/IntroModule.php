<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntroModule extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'intro_modules';
    protected $primaryKey = 'intro_module_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'category',
        'display_order',
        'is_featured',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted'
    ];

    protected $appends = ['icon_url', 'image_url'];

    protected $casts = [
        'is_featured' => 'boolean'
    ];

    public function getIconUrlAttribute()
    {
        return !empty($this->icon) ? asset('public/uploads/intro/modules/' . $this->icon) : null;
    }

    public function getImageUrlAttribute()
    {
        return !empty($this->image) ? asset('public/uploads/intro/modules/' . $this->image) : null;
    }
}
