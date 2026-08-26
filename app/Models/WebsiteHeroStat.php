<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteHeroStat extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'hero_stat_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'hero_stat_id',
        'business_id',
        'value',
        'label',
        'icon',
        'icon_color',
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
