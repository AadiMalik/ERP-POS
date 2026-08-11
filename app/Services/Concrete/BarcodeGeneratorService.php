<?php

namespace App\Services\Concrete;

use App\Enums\BarcodeType;
use App\Models\ProductVariation;
use Exception;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

class BarcodeGeneratorService
{
    // Payload length (digits, excluding the check digit) for standards that use a fixed-length numeric check-digit scheme.
    protected $payloadLengths = [
        BarcodeType::EAN13 => 12,
        BarcodeType::EAN8  => 7,
        BarcodeType::UPCA  => 11,
    ];

    protected $picqerTypeMap = [
        BarcodeType::CODE128 => BarcodeGeneratorSVG::TYPE_CODE_128,
        BarcodeType::EAN13   => BarcodeGeneratorSVG::TYPE_EAN_13,
        BarcodeType::EAN8    => BarcodeGeneratorSVG::TYPE_EAN_8,
        BarcodeType::UPCA    => BarcodeGeneratorSVG::TYPE_UPC_A,
    ];

    /**
     * Generate a syntactically valid, business-unique barcode value for the given standard.
     */
    public function generateBarcodeValue(string $type, ?string $prefix, int $code128Length, string $businessId): string
    {
        $maxAttempts = 25;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $value = $this->buildCandidate($type, $prefix, $code128Length);

            if (!$this->existsForBusiness($businessId, $value)) {
                return $value;
            }
        }

        throw new Exception('Unable to generate a unique barcode after multiple attempts.');
    }

    /**
     * Validate a manually entered/scanned barcode against the check-digit rules of its declared standard.
     * CODE128 has no fixed check-digit scheme, so any non-empty value is accepted.
     */
    public function isValidManualBarcode(string $value, string $type): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        if (!isset($this->payloadLengths[$type])) {
            return true;
        }

        $expectedLength = $this->payloadLengths[$type] + 1;

        if (strlen($value) !== $expectedLength || !ctype_digit($value)) {
            return false;
        }

        $payload = substr($value, 0, -1);
        $checkDigit = (int) substr($value, -1);

        return $this->checksumDigit($payload) === $checkDigit;
    }

    /**
     * Guess the real standard a manually entered/scanned value belongs to, by checking its
     * length and check digit against EAN-13/EAN-8/UPC-A in turn, falling back to CODE128 for
     * anything that doesn't match (alphanumeric values, or numeric values with no valid check
     * digit under any of those standards). Used when a manual barcode is supplied without an
     * explicit type, so the barcode is rendered as its real-world symbol rather than a generic one.
     */
    public function detectBarcodeType(string $value): string
    {
        foreach ([BarcodeType::EAN13, BarcodeType::EAN8, BarcodeType::UPCA] as $type) {
            if ($this->isValidManualBarcode($value, $type)) {
                return $type;
            }
        }

        return BarcodeType::CODE128;
    }

    /**
     * Render a barcode value to inline SVG markup for the given standard.
     */
    public function renderSvg(string $value, string $type): string
    {
        $generator = new BarcodeGeneratorSVG();
        $picqerType = $this->picqerTypeMap[$type] ?? BarcodeGeneratorSVG::TYPE_CODE_128;

        return $generator->getBarcode($value, $picqerType);
    }

    /**
     * Render a barcode value to a PNG image (raw bytes) for the given standard.
     */
    public function renderPng(string $value, string $type): string
    {
        $generator = new BarcodeGeneratorPNG();
        $picqerType = $this->picqerTypeMap[$type] ?? BarcodeGeneratorPNG::TYPE_CODE_128;

        return $generator->getBarcode($value, $picqerType);
    }

    protected function buildCandidate(string $type, ?string $prefix, int $code128Length): string
    {
        if (isset($this->payloadLengths[$type])) {
            $payloadLength = $this->payloadLengths[$type];
            $payload = $this->randomDigits($payloadLength);

            return $payload . $this->checksumDigit($payload);
        }

        // CODE128 - fixed-width numeric sequence, optionally prefixed by the business's configured prefix.
        $prefix = (string) $prefix;
        $remaining = max($code128Length - strlen($prefix), 4);

        return $prefix . $this->randomDigits($remaining);
    }

    protected function randomDigits(int $length): string
    {
        $digits = '';

        for ($i = 0; $i < $length; $i++) {
            $digits .= random_int(0, 9);
        }

        return $digits;
    }

    /**
     * Standard GS1 mod-10 check digit: weight 3 on the digit nearest the check digit
     * (rightmost payload digit), alternating with weight 1 moving left. Used by EAN-13,
     * EAN-8, and UPC-A alike.
     */
    protected function checksumDigit(string $payload): int
    {
        $sum = 0;
        $length = strlen($payload);

        foreach (str_split($payload) as $index => $digit) {
            $distanceFromRight = $length - $index;
            $weight = ($distanceFromRight % 2 === 1) ? 3 : 1;
            $sum += ((int) $digit) * $weight;
        }

        $mod = $sum % 10;

        return $mod === 0 ? 0 : 10 - $mod;
    }

    protected function existsForBusiness(string $businessId, string $value): bool
    {
        return ProductVariation::where('business_id', $businessId)
            ->where('barcode', $value)
            ->where('is_deleted', 0)
            ->exists();
    }
}
