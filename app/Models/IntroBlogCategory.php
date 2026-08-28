<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntroBlogCategory extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'intro_blog_categories';
    protected $primaryKey = 'intro_blog_category_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'display_order',
        'status',
        'seo_title',
        'meta_description',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted'
    ];
}
