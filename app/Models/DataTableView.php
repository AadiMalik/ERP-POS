<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataTableView extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'datatable_view_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'datatable_views';

    protected $fillable = [
        'datatable_view_id',
        'user_id',
        'business_id',
        'table_key',
        'name',
        'is_default',
        'visible_columns',
        'column_order',
        'sort_column',
        'sort_dir',
        'page_length',
        'export_columns',
        'filters',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    protected $casts = [
        'visible_columns' => 'array',
        'column_order' => 'array',
        'export_columns' => 'array',
        'filters' => 'array',
        'is_default' => 'boolean',
        'is_deleted' => 'boolean',
        'page_length' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
