<?php

namespace App\Enums;

class BarcodeType
{
    const CODE128 = 'CODE128';
    const EAN13   = 'EAN13';
    const EAN8    = 'EAN8';
    const UPCA    = 'UPCA';

    public static function getOptions()
    {
        return [
            self::CODE128 => 'Code 128',
            self::EAN13   => 'EAN-13',
            self::EAN8    => 'EAN-8',
            self::UPCA    => 'UPC-A',
        ];
    }
}
