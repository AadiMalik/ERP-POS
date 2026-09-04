<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'purchase_return_detail_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'purchase_return_detail_id',
        'purchase_return_id',
        'purchase_id',
        'purchase_detail_id',
        'good_receipt_note_id',
        'good_receipt_note_detail_id',
        'product_id',
        'product_variation_id',
        'product_variation_unit_conversion_id',
        'unit_id',
        'received_quantity',
        'already_returned_quantity',
        'return_quantity',
        'conversion_factor',
        'base_quantity',
        'unit_price',
        'discount',
        'discount_amount',
        'tax',
        'tax_amount',
        'subtotal',
        'total',
        'reason',
        'description',
        'serial_numbers',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id', 'purchase_return_id');
    }

    public function purchaseDetail()
    {
        return $this->belongsTo(PurchaseDetail::class, 'purchase_detail_id', 'purchase_detail_id');
    }

    public function goodReceiptNoteDetail()
    {
        return $this->belongsTo(GoodReceiptNoteDetail::class, 'good_receipt_note_detail_id', 'good_receipt_note_detail_id');
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
