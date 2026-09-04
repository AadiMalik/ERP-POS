<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariationSerialMovement extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'product_variation_serial_movement_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'product_variation_serial_movement_id',
        'product_variation_serial_number_id',
        'business_id',
        'branch_id',
        'event_type',
        'from_warehouse_id',
        'to_warehouse_id',
        'reference_type',
        'reference_id',
        'notes',
        'createdby_id',
        'date_created',
    ];

    public function serial()
    {
        return $this->belongsTo(ProductVariationSerialNumber::class, 'product_variation_serial_number_id', 'product_variation_serial_number_id');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id', 'warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id', 'warehouse_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
