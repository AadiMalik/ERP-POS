{{--
    Page/Printer settings, emitted into @section('css') by each browser-print view.
    Optional: $print_config (App\Support\Print\PrintConfig)
--}}
@php
    $pc = $print_config ?? new \App\Support\Print\PrintConfig(config('print_defaults'));

    $paper_size = $pc->page('paper_size', 'A4');
    $orientation = $pc->page('orientation');
    $margin_top = $pc->page('margin_top_mm', 15);
    $margin_bottom = $pc->page('margin_bottom_mm', 20);
    $margin_left = $pc->page('margin_left_mm', 12);
    $margin_right = $pc->page('margin_right_mm', 12);

    if (strtolower($paper_size) === 'custom' && $pc->page('custom_width_mm') && $pc->page('custom_height_mm')) {
        $size_css = $pc->page('custom_width_mm') . 'mm ' . $pc->page('custom_height_mm') . 'mm';
    } else {
        $size_css = $paper_size . ($orientation ? ' ' . $orientation : '');
    }

    $scale = (int) $pc->page('page_scale_percent', 100);
@endphp
<style>
    @page {
        size: {{ $size_css }};
        margin: {{ $margin_top }}mm {{ $margin_right }}mm {{ $margin_bottom }}mm {{ $margin_left }}mm;
    }

    body {
        font-family: {{ $pc->page('font_family', 'Arial, Helvetica, sans-serif') }};
        line-height: {{ $pc->page('line_height', 1.4) }};
    }

    .print-page {
        padding: {{ $margin_top }}mm {{ $margin_right }}mm {{ $margin_bottom }}mm {{ $margin_left }}mm;
        font-size: {{ (int) $pc->page('base_font_size_pt', 12) }}px;
        @if ($scale !== 100)
            zoom: {{ $scale }}%;
        @endif
    }

    .print-header .company-logo {
        max-width: {{ (int) $pc->page('logo_max_width_px', 60) }}px;
        max-height: {{ (int) $pc->page('logo_max_height_px', 60) }}px;
        object-fit: {{ $pc->page('logo_scaling', 'contain') }};
    }

    @if (!$pc->page('show_grid_lines', true))
        .print-table th,
        .print-table td {
            border-color: transparent;
        }
    @endif

    @if (!$pc->page('print_background_colors', true))
        .print-table th {
            background: none !important;
        }
    @endif

    @media print {
        @page {
            size: {{ $size_css }};
            margin: {{ $margin_top }}mm {{ $margin_right }}mm {{ $margin_bottom }}mm {{ $margin_left }}mm;
        }

        @if (!$pc->page('repeat_table_header', true))
            thead {
                display: table-row-group !important;
            }
        @endif
    }
</style>
