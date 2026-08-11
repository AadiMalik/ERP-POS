<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarcodeSetting extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'business_id',
        'enable_barcode',
        'enable_qr_code',
        'barcode_type',
        'barcode_prefix',
        'code128_length',
        'qr_data_source',
        'qr_size_px',
        'qr_error_correction',
        'label_config',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    protected $casts = [
        'label_config' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
