@php
    $header_fields = [
        'logo' => ['label' => __('settings.print_field_logo'), 'align' => 'left'],
        'company_name' => ['label' => __('settings.print_field_company_name'), 'align' => 'left'],
        'branch_name' => ['label' => __('settings.print_field_branch_name'), 'align' => 'left'],
        'address' => ['label' => __('settings.print_field_address'), 'align' => 'left'],
        'phone' => ['label' => __('settings.print_field_phone'), 'align' => 'left'],
        'email' => ['label' => __('settings.print_field_email'), 'align' => 'left'],
        'website' => ['label' => __('settings.print_field_website'), 'align' => 'left'],
        'ntn' => ['label' => __('settings.print_field_ntn'), 'align' => 'left'],
        'strn' => ['label' => __('settings.print_field_strn'), 'align' => 'left'],
        'tax_reg_no' => ['label' => __('settings.print_field_tax_reg_no'), 'align' => 'left'],
        'currency' => ['label' => __('settings.print_field_currency'), 'align' => 'left'],
        'document_title' => ['label' => __('settings.print_field_document_title'), 'align' => 'right'],
        'document_no' => ['label' => __('settings.print_field_document_no'), 'align' => 'right'],
        'voucher_no' => ['label' => __('settings.print_field_voucher_no'), 'align' => 'right'],
        'reference_no' => ['label' => __('settings.print_field_reference_no'), 'align' => 'right'],
        'date' => ['label' => __('settings.print_field_date'), 'align' => 'right'],
        'time' => ['label' => __('settings.print_field_time'), 'align' => 'right'],
        'printed_by' => ['label' => __('settings.print_field_printed_by'), 'align' => 'right'],
        'printed_on' => ['label' => __('settings.print_field_printed_on'), 'align' => 'right'],
        'status_badge' => ['label' => __('settings.print_field_status_badge'), 'align' => 'right'],
        'posting_status_badge' => ['label' => __('settings.print_field_posting_status_badge'), 'align' => 'right'],
        'approval_status' => ['label' => __('settings.print_field_approval_status'), 'align' => 'right'],
        'qr_code' => ['label' => __('settings.print_field_qr_code'), 'align' => 'right'],
        'barcode' => ['label' => __('settings.print_field_barcode'), 'align' => 'right'],
    ];
    $watermark = $header['watermark'] ?? [];

    $header_templates = [
        'classic'   => __('settings.print_template_classic'),
        'modern'    => __('settings.print_template_modern'),
        'minimal'   => __('settings.print_template_minimal'),
        'boxed'     => __('settings.print_template_boxed'),
        'elegant'   => __('settings.print_template_elegant'),
        'corporate' => __('settings.print_template_corporate'),
        'bold'      => __('settings.print_template_bold'),
        'compact'   => __('settings.print_template_compact'),
    ];
    $selected_header_template = $header['template'] ?? 'classic';

    $preview_business = (object) [
        'logo' => null,
        'name' => __('settings.print_preview_business_name'),
        'address' => __('settings.print_preview_address'),
        'city' => null,
        'state' => null,
        'country' => null,
        'phone' => '+92 300 1234567',
        'email' => 'info@business.com',
        'website' => 'www.business.com',
        'fbrSetting' => null,
        'praSetting' => null,
    ];
    $print_css_url = asset('public/assets/css/print.css') . '?v=' . filemtime(public_path('assets/css/print.css'));
@endphp

<h6>{{ __('settings.print_template_choose_header') }}</h6>
<div id="headerTemplateCarousel" class="carousel slide pt-template-carousel mb-4" data-bs-interval="false">
    <div class="carousel-inner">
        @foreach ($header_templates as $key => $label)
            @php
                $preview_config = new \App\Support\Print\PrintConfig([
                    'header_config' => array_merge($header, ['template' => $key]),
                ]);
                $preview_html = view('admin.partials.print.header', [
                    'business' => $preview_business,
                    'branch' => null,
                    'title' => __('settings.print_preview_document_title'),
                    'doc_no' => 'INV-00231',
                    'doc_date' => date('d M, Y'),
                    'reference' => [],
                    'print_config' => $preview_config,
                ])->render();
                $preview_doc = '<html><head><link rel="stylesheet" href="' . $print_css_url . '">'
                    . '<style>body{margin:0;background:#fff;}</style></head><body>'
                    . '<div style="padding:15mm 12mm 6mm 12mm;">' . $preview_html . '</div></body></html>';
            @endphp
            <div class="carousel-item {{ $selected_header_template === $key ? 'active' : '' }}">
                <div class="pt-template-slide">
                    <iframe class="pt-template-frame-lg" srcdoc="{{ $preview_doc }}"></iframe>
                    <div class="pt-template-slide-footer">
                        <label class="pt-template-pick">
                            <input type="radio" name="header_config[template]" value="{{ $key }}"
                                {{ $selected_header_template === $key ? 'checked' : '' }}>
                            <strong>{{ $label }}</strong>
                        </label>
                        <span class="pt-template-position">{{ $loop->iteration }} / {{ $loop->count }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#headerTemplateCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#headerTemplateCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
    </button>
</div>

<h6>{{ __('settings.print_th_field') }}</h6>
<div class="table-responsive">
    <table class="table pt-field-table">
        <thead>
            <tr>
                <th>{{ __('settings.print_th_field') }}</th>
                <th>{{ __('settings.print_th_visible') }}</th>
                <th>{{ __('settings.print_th_alignment') }}</th>
                <th>{{ __('settings.print_th_order') }}</th>
                <th>{{ __('settings.print_th_font_size') }}</th>
                <th>{{ __('settings.print_th_font_weight') }}</th>
                <th>{{ __('settings.print_th_color') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($header_fields as $key => $meta)
                @php $f = $header['fields'][$key] ?? []; @endphp
                <tr>
                    <td>{{ $meta['label'] }}</td>
                    <td>
                        <input type="hidden" name="header_config[fields][{{ $key }}][visible]" value="0">
                        <input type="checkbox" name="header_config[fields][{{ $key }}][visible]" value="1"
                            {{ ($f['visible'] ?? false) ? 'checked' : '' }}>
                    </td>
                    <td>
                        <select class="form-select form-select-sm" name="header_config[fields][{{ $key }}][align]">
                            @foreach ([
                                'left' => __('settings.print_align_left'),
                                'center' => __('settings.print_align_center'),
                                'right' => __('settings.print_align_right'),
                            ] as $val => $label)
                                <option value="{{ $val }}" {{ ($f['align'] ?? $meta['align']) === $val ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm"
                            name="header_config[fields][{{ $key }}][order]" value="{{ $f['order'] ?? 0 }}">
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm"
                            name="header_config[fields][{{ $key }}][font_size]" value="{{ $f['font_size'] ?? '' }}">
                    </td>
                    <td>
                        <select class="form-select form-select-sm" name="header_config[fields][{{ $key }}][font_weight]">
                            @foreach ([
                                'normal' => __('settings.print_weight_normal'),
                                'bold' => __('settings.print_weight_bold'),
                                '800' => __('settings.print_weight_extra_bold'),
                            ] as $val => $label)
                                <option value="{{ $val }}" {{ ($f['font_weight'] ?? '') === $val ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="color" class="form-control form-control-color form-control-sm"
                            name="header_config[fields][{{ $key }}][color]" value="{{ $f['color'] ?? '#1a1a1a' }}">
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<hr>
<h6>{{ __('settings.print_watermark') }}</h6>
<div class="row g-3">
    <div class="col-md-2">
        <div class="form-check">
            <input type="hidden" name="header_config[watermark][visible]" value="0">
            <input class="form-check-input" type="checkbox" name="header_config[watermark][visible]" value="1"
                {{ ($watermark['visible'] ?? false) ? 'checked' : '' }}>
            <label class="form-check-label">{{ __('settings.print_watermark_visible') }}</label>
        </div>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('settings.print_watermark_text') }}</label>
        <input type="text" class="form-control" name="header_config[watermark][text]"
            value="{{ $watermark['text'] ?? 'DRAFT' }}" placeholder="{{ __('settings.print_watermark_text_placeholder') }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('settings.print_watermark_color') }}</label>
        <input type="color" class="form-control form-control-color"
            value="{{ $watermark['color'] ?? '#cccccc' }}" name="header_config[watermark][color]">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('settings.print_watermark_opacity') }}</label>
        <input type="number" step="0.05" min="0" max="1" class="form-control"
            name="header_config[watermark][opacity]" value="{{ $watermark['opacity'] ?? 0.15 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('settings.print_watermark_font_size') }}</label>
        <input type="number" class="form-control" name="header_config[watermark][font_size]"
            value="{{ $watermark['font_size'] ?? 60 }}">
    </div>
    <div class="col-md-1">
        <label class="form-label">{{ __('settings.print_watermark_rotate') }}</label>
        <input type="number" class="form-control" name="header_config[watermark][rotation_deg]"
            value="{{ $watermark['rotation_deg'] ?? -30 }}">
    </div>
</div>
