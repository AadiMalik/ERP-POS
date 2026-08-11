<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Models\ProductVariation;
use App\Services\Concrete\Admin\BarcodeService;
use App\Services\Concrete\Admin\SettingService;
use App\Services\Concrete\BarcodeGeneratorService;
use App\Services\Concrete\QrCodeGeneratorService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BarcodeController extends Controller
{
    use ResponseAPI;

    protected $barcode_service;
    protected $setting_service;
    protected $barcode_generator;
    protected $qr_generator;

    public function __construct(
        BarcodeService $barcode_service,
        SettingService $setting_service,
        BarcodeGeneratorService $barcode_generator,
        QrCodeGeneratorService $qr_generator
    ) {
        $this->barcode_service = $barcode_service;
        $this->setting_service = $setting_service;
        $this->barcode_generator = $barcode_generator;
        $this->qr_generator = $qr_generator;
    }

    /**
     * Resolve a scanned/typed barcode (or SKU) to the matching product + variation.
     */
    public function lookup(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $result = $this->barcode_service->lookup($request->code);

            return $result
                ? $this->success(Message::SUCCESS, $result)
                : $this->error('No product found for this code.');
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    /**
     * Bulk-regenerate barcodes/QR codes for the given variations.
     */
    public function regenerate(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'product_variation_ids' => 'required|array|min:1',
            'product_variation_ids.*' => 'exists:product_variations,product_variation_id',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $result = $this->barcode_service->regenerate(
                $request->product_variation_ids,
                (bool) $request->overwrite_manual
            );

            return $this->success(Message::UPDATE, $result);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    /**
     * Render a variation's barcode or QR code as an inline image, on demand from the
     * persisted value - no rendered image is ever stored, so this always reflects the
     * variation's current (permanent) code.
     */
    public function render($product_variation_id, Request $request)
    {
        $variation = ProductVariation::find($product_variation_id);

        if (!$variation) {
            abort(404);
        }

        $type = $request->type ?? 'barcode';

        if ($type === 'qr') {
            if (empty($variation->qr_code)) {
                abort(404);
            }

            $setting = $this->setting_service->getBarcodeSetting($variation->business_id);
            $svg = $this->qr_generator->renderSvg($variation->qr_code, $setting->qr_size_px, $setting->qr_error_correction);
        } else {
            if (empty($variation->barcode)) {
                abort(404);
            }

            $svg = $this->barcode_generator->renderSvg($variation->barcode, $variation->barcode_type ?? 'CODE128');
        }

        return response($svg, 200)->header('Content-Type', 'image/svg+xml');
    }

    /**
     * In-browser label preview (also used for printing via the browser's print dialog,
     * matching the print/preview convention used elsewhere in this app).
     */
    public function labelPreview(Request $request)
    {
        $ids = (array) ($request->product_variation_ids ?? []);

        $variations = ProductVariation::with('product', 'unit')
            ->whereIn('product_variation_id', $ids)
            ->where('is_deleted', 0)
            ->get();

        if ($variations->isEmpty()) {
            abort(404);
        }

        $businessId = $variations->first()->business_id;
        $setting = $this->setting_service->getBarcodeSetting($businessId);
        $labelConfig = $setting->label_config ?? config('barcode_label_defaults');

        return view('admin.barcode.print.label', compact('variations', 'setting', 'labelConfig'));
    }

    /**
     * Downloadable label sheet PDF, sized to the configured label dimensions.
     */
    public function labelPdf(Request $request)
    {
        $ids = (array) ($request->product_variation_ids ?? []);

        $variations = ProductVariation::with('product', 'unit')
            ->whereIn('product_variation_id', $ids)
            ->where('is_deleted', 0)
            ->get();

        if ($variations->isEmpty()) {
            abort(404);
        }

        $businessId = $variations->first()->business_id;
        $setting = $this->setting_service->getBarcodeSetting($businessId);
        $labelConfig = $setting->label_config ?? config('barcode_label_defaults');

        $pageWidthMm = ($labelConfig['width_mm'] ?? 40) * ($labelConfig['columns_per_row'] ?? 3);
        $pageHeightMm = 297; // A4 height, labels flow/paginate down the sheet

        return Pdf::loadView('admin.barcode.print.label', compact('variations', 'setting', 'labelConfig'))
            ->setPaper([0, 0, $pageWidthMm * 2.8346, $pageHeightMm * 2.8346])
            ->stream('barcode-labels.pdf');
    }
}
