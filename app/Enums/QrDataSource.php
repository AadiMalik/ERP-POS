<?php

namespace App\Enums;

class QrDataSource
{
    const BARCODE_VALUE      = 'barcode_value';
    const SKU                = 'sku';
    const INTERNAL_REFERENCE = 'internal_reference';

    public static function getOptions()
    {
        return [
            self::INTERNAL_REFERENCE => 'Internal Reference (recommended)',
            self::BARCODE_VALUE      => 'Barcode Value',
            self::SKU                => 'SKU',
        ];
    }
}
