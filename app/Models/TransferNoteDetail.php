<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferNoteDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'transfer_note_detail_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'transfer_note_detail_id',
        'transfer_note_id',
        'product_id',
        'product_variation_id',
        'product_variation_unit_conversion_id',
        'unit_id',
        'conversion_factor',
        'available_quantity',
        'transfer_quantity',
        'received_quantity',
        'base_quantity',
        'unit_cost',
        'total_value',
        'description',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    public function transferNote()
    {
        return $this->belongsTo(TransferNote::class, 'transfer_note_id', 'transfer_note_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id', 'product_variation_id');
    }

    public function productVariationUnitConversion()
    {
        return $this->belongsTo(ProductVariationUnitConversion::class, 'product_variation_unit_conversion_id', 'product_variation_unit_conversion_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'unit_id');
    }
}
