<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WasteDamageExpiryDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'waste_damage_expiry_detail_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'waste_damage_expiry_detail_id',
        'waste_damage_expiry_id',
        'product_id',
        'product_variation_id',
        'unit_id',
        'product_variation_batch_id',
        'batch_no',
        'expiry_date',
        'quantity',
        'unit_cost',
        'value',
        'loss_type',
        'loss_reason_id',
        'notes',
        'serial_numbers',

        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    public function wasteDamageExpiry()
    {
        return $this->belongsTo(WasteDamageExpiry::class, 'waste_damage_expiry_id', 'waste_damage_expiry_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id', 'product_variation_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'unit_id');
    }

    public function productVariationBatch()
    {
        return $this->belongsTo(ProductVariationBatch::class, 'product_variation_batch_id', 'product_variation_batch_id');
    }

    public function lossReason()
    {
        return $this->belongsTo(LossReason::class, 'loss_reason_id', 'loss_reason_id');
    }
}
