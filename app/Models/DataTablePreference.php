<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataTablePreference extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'datatable_preference_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'datatable_preferences';

    protected $fillable = [
        'datatable_preference_id',
        'user_id',
        'business_id',
        'table_key',
        'visible_columns',
        'column_order',
        'sort_column',
        'sort_dir',
        'page_length',
        'export_columns',
        'filters',
        'active_view_id',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    protected $casts = [
        'visible_columns' => 'array',
        'column_order' => 'array',
        'export_columns' => 'array',
        'filters' => 'array',
        'page_length' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
