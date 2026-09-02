<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetCategory extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'fixed_asset_category_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'fixed_asset_category_id',
        'business_id',
        'code',
        'name',
        'description',
        'default_useful_life_years',
        'default_depreciation_method',
        'default_residual_percent',
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
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function fixedAssets()
    {
        return $this->hasMany(FixedAsset::class, 'fixed_asset_category_id', 'fixed_asset_category_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
