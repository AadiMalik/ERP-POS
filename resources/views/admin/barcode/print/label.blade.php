@php
    $barcodeGenerator = app(\App\Services\Concrete\BarcodeGeneratorService::class);
    $qrGenerator = app(\App\Services\Concrete\QrCodeGeneratorService::class);

    $widthMm = $labelConfig['width_mm'] ?? 40;
    $heightMm = $labelConfig['height_mm'] ?? 25;
    $columns = $labelConfig['columns_per_row'] ?? 3;
    $spacingMm = $labelConfig['spacing_mm'] ?? 2;
    $alignment = $labelConfig['alignment'] ?? 'center';
    $fontSizePt = $labelConfig['font_size_pt'] ?? 8;
@endphp
@extends('layouts.print')

@section('title', __('barcodes.title'))

@section('css')
    <style>
        .label-sheet {
            display: flex;
            flex-wrap: wrap;
            gap: {{ $spacingMm }}mm;
        }

        .label {
            width: {{ $widthMm }}mm;
            height: {{ $heightMm }}mm;
            box-sizing: border-box;
            border: 1px dashed #999;
            padding: 1.5mm;
            text-align: {{ $alignment }};
            font-size: {{ $fontSizePt }}pt;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .label .label-line {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .label img {
            max-width: 100%;
            height: auto;
        }
    </style>
@endsection

@section('content')
    <div class="label-sheet">
        @foreach ($variations as $variation)
            <div class="label">
                @if ($labelConfig['show_product_name'] ?? true)
                    <div class="label-line">{{ $variation->product->name ?? '' }}</div>
                @endif
                @if ($labelConfig['show_variation_name'] ?? true)
                    <div class="label-line">{{ $variation->name }}</div>
                @endif
                @if ($labelConfig['show_sku'] ?? true)
                    <div class="label-line">{{ __('barcodes.sku_label') }}: {{ $variation->sku }}</div>
                @endif

                @if (($labelConfig['show_barcode'] ?? true) && !empty($variation->barcode))
                    {!! $barcodeGenerator->renderSvg($variation->barcode, $variation->barcode_type ?? 'CODE128') !!}
                @endif
                @if (($labelConfig['show_barcode_value_text'] ?? true) && !empty($variation->barcode))
                    <div class="label-line">{{ $variation->barcode }}</div>
                @endif

                @if (($labelConfig['show_qr_code'] ?? false) && !empty($variation->qr_code))
                    {!! $qrGenerator->renderSvg($variation->qr_code, 80, $setting->qr_error_correction ?? 'M') !!}
                @endif
            </div>
        @endforeach
    </div>
@endsection
