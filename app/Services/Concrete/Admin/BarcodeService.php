<?php

namespace App\Services\Concrete\Admin;

use App\Models\ProductVariation;
use App\Services\Concrete\BarcodeGeneratorService;
use App\Services\Concrete\QrCodeGeneratorService;
use Illuminate\Support\Facades\Auth;

class BarcodeService
{
    protected $barcode_generator;
    protected $qr_generator;
    protected $setting_service;

    public function __construct(
        BarcodeGeneratorService $barcode_generator,
        QrCodeGeneratorService $qr_generator,
        SettingService $setting_service
    ) {
        $this->barcode_generator = $barcode_generator;
        $this->qr_generator = $qr_generator;
        $this->setting_service = $setting_service;
    }

    /**
     * Generate (or accept a manually supplied) barcode + QR code for a variation, per the
     * business's Barcode & QR settings. Once a variation already has a barcode, this is a
     * no-op unless $force is true, so settings changes never retroactively affect existing
     * codes and codes stay permanently associated with the variation.
     */
    public function generateForVariation(
        ProductVariation $variation,
        ?string $manualBarcode = null,
        ?string $manualBarcodeType = null,
        bool $force = false
    ) {
        if (!empty($variation->barcode) && !$force) {
            return $variation;
        }

        $setting = $this->setting_service->getBarcodeSetting($variation->business_id);

        $manualBarcode = trim((string) $manualBarcode) !== '' ? trim($manualBarcode) : null;

        if ($manualBarcode) {
            $type = $manualBarcodeType ?: $this->barcode_generator->detectBarcodeType($manualBarcode);
            $variation->barcode = $manualBarcode;
            $variation->barcode_type = $type;
            $variation->barcode_is_manual = true;
            $variation->barcode_generated_at = now();
        } elseif ($setting->enable_barcode) {
            $variation->barcode = $this->barcode_generator->generateBarcodeValue(
                $setting->barcode_type,
                $setting->barcode_prefix,
                $setting->code128_length,
                $variation->business_id
            );
            $variation->barcode_type = $setting->barcode_type;
            $variation->barcode_is_manual = false;
            $variation->barcode_generated_at = now();
        } else {
            $variation->barcode = null;
            $variation->barcode_type = null;
            $variation->barcode_is_manual = false;
            $variation->barcode_generated_at = null;
        }

        // QR is always generated when enabled, regardless of whether the barcode was manual.
        if ($setting->enable_qr_code) {
            $variation->qr_code = $this->qr_generator->buildPayload($setting->qr_data_source, $variation);
            $variation->qr_generated_at = now();
        } else {
            $variation->qr_code = null;
            $variation->qr_generated_at = null;
        }

        $variation->save();

        return $variation;
    }

    /**
     * Bulk-regenerate barcodes/QR codes for the given variations. Manually entered
     * (manufacturer) barcodes are skipped unless $overwriteManual is explicitly set, to
     * protect real manufacturer codes from accidental replacement.
     */
    public function regenerate(array $productVariationIds, bool $overwriteManual = false)
    {
        $variations = ProductVariation::whereIn('product_variation_id', $productVariationIds)
            ->where('is_deleted', 0)
            ->get();

        $regenerated = [];
        $skipped = [];

        foreach ($variations as $variation) {
            if ($variation->barcode_is_manual && !$overwriteManual) {
                $skipped[] = $variation->product_variation_id;
                continue;
            }

            $variation->barcode = null;
            $this->generateForVariation($variation, null, null, true);
            $regenerated[] = $variation->product_variation_id;
        }

        return [
            'regenerated' => $regenerated,
            'skipped' => $skipped,
        ];
    }

    /**
     * Resolve a scanned/typed value to the matching product + variation, scoped to the
     * current business. Falls back to matching against SKU since scanners are sometimes
     * pointed at an SKU label rather than a barcode.
     */
    public function lookup(string $code, ?string $businessId = null)
    {
        $businessId = $businessId ?? Auth::user()->business_id;
        $code = trim($code);

        $variation = ProductVariation::with('product', 'unit', 'purchaseUnit', 'saleUnit')
            ->where('business_id', $businessId)
            ->where('is_deleted', 0)
            ->where(function ($query) use ($code) {
                $query->where('barcode', $code)->orWhere('sku', $code);
            })
            ->first();

        if (!$variation) {
            return null;
        }

        return [
            'product' => $variation->product,
            'variation' => $variation,
        ];
    }
}
