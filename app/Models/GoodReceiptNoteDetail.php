<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodReceiptNoteDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'good_receipt_note_detail_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'good_receipt_note_detail_id',
        'good_receipt_note_id',
        'purchase_id',
        'purchase_detail_id',
        'product_variation_batch_id',
        'batch_no',
        'manufacturing_date',
        'expiry_date',
        'product_id',
        'product_variation_id',
        'product_variation_unit_conversion_id',
        'received_quantity',
        'base_quantity',
        'unit_id',
        'conversion_factor',
        'unit_price',
        'total',
    ];

    public function goodReceiptNote()
    {
        return $this->belongsTo(GoodReceiptNote::class, 'good_receipt_note_id', 'good_receipt_note_id');
    }

    public function purchaseDetail()
    {
        return $this->belongsTo(PurchaseDetail::class, 'purchase_detail_id', 'purchase_detail_id');
    }

    public function productVariationBatch()
    {
        return $this->belongsTo(ProductVariationBatch::class, 'product_variation_batch_id', 'product_variation_batch_id');
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

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }
}
