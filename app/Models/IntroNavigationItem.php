<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntroNavigationItem extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'intro_navigation_items';
    protected $primaryKey = 'intro_navigation_item_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'label',
        'url',
        'section_key',
        'match_key',
        'location',
        'parent_id',
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

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'intro_navigation_item_id')
            ->where('is_deleted', 0)
            ->orderBy('display_order');
    }
}
