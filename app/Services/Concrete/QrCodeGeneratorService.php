<?php

namespace App\Services\Concrete;

use App\Enums\QrDataSource;
use App\Models\ProductVariation;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeGeneratorService
{
    /**
     * Resolve the configured data source into the actual string to encode into the QR code.
     * There is no public/customer-facing product page in this application, so
     * "internal_reference" links back to the admin product edit screen rather than a
     * storefront URL.
     */
    public function buildPayload(string $dataSource, ProductVariation $variation): string
    {
        switch ($dataSource) {
            case QrDataSource::BARCODE_VALUE:
                return (string) $variation->barcode;

            case QrDataSource::SKU:
                return (string) $variation->sku;

            case QrDataSource::INTERNAL_REFERENCE:
            default:
                return route('product.edit', $variation->product_id) . '?variation=' . $variation->product_variation_id;
        }
    }

    /**
     * Render a QR payload to inline SVG markup.
     */
    public function renderSvg(string $payload, int $size = 200, string $errorCorrection = 'M'): string
    {
        return QrCode::format('svg')
            ->size($size)
            ->errorCorrection($errorCorrection)
            ->generate($payload);
    }

    /**
     * Render a QR payload to a PNG image (raw bytes).
     */
    public function renderPng(string $payload, int $size = 200, string $errorCorrection = 'M'): string
    {
        return QrCode::format('png')
            ->size($size)
            ->errorCorrection($errorCorrection)
            ->generate($payload);
    }
}
