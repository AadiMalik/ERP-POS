@php
    $footer_sections = [
        'footer_notes' => __('settings.print_footer_notes'),
        'thank_you_message' => __('settings.print_footer_thank_you'),
        'terms_and_conditions' => __('settings.print_footer_terms'),
        'return_policy' => __('settings.print_footer_return_policy'),
        'payment_instructions' => __('settings.print_footer_payment_instructions'),
        'bank_details' => __('settings.print_footer_bank_details'),
        'contact_info' => __('settings.print_footer_contact_info'),
        'website' => __('settings.print_footer_website'),
        'social_links' => __('settings.print_footer_social_links'),
        'confidential_notice' => __('settings.print_footer_confidential'),
        'custom_text_block' => __('settings.print_footer_custom_text'),
    ];
    $sections = $footer['sections'] ?? [];

    $footer_templates = [
        'classic'   => __('settings.print_template_classic'),
        'minimal'   => __('settings.print_template_minimal'),
        'boxed'     => __('settings.print_template_boxed'),
        'bold'      => __('settings.print_template_bold'),
        'centered'  => __('settings.print_template_centered'),
        'corporate' => __('settings.print_template_corporate'),
        'divided'   => __('settings.print_template_divided'),
        'compact'   => __('settings.print_template_compact'),
    ];
    $selected_footer_template = $footer['template'] ?? 'classic';
    $print_css_url = asset('public/assets/css/print.css') . '?v=' . filemtime(public_path('assets/css/print.css'));
@endphp

<h6>{{ __('settings.print_template_choose_footer') }}</h6>
<div id="footerTemplateCarousel" class="carousel slide pt-template-carousel mb-4" data-bs-interval="false">
    <div class="carousel-inner">
        @foreach ($footer_templates as $key => $label)
            @php
                // The gallery preview always shows a sample note + signature row,
                // regardless of what's actually toggled on below, so an all-hidden
                // default config still lets the admin compare all 8 layouts.
                $preview_footer = array_merge($footer, ['template' => $key]);
                $preview_footer['sections']['thank_you_message'] = [
                    'visible' => true,
                    'text' => __('settings.print_preview_thank_you'),
                ];
                $preview_footer['signature_lines']['visible'] = true;

                $preview_config = new \App\Support\Print\PrintConfig([
                    'footer_config' => $preview_footer,
                ]);
                $preview_html = view('admin.partials.print.footer', [
                    'signatories' => [__('settings.print_preview_prepared_by'), __('settings.print_preview_approved_by')],
                    'print_config' => $preview_config,
                ])->render();
                $preview_doc = '<html><head><link rel="stylesheet" href="' . $print_css_url . '">'
                    . '<style>body{margin:0;background:#fff;}</style></head><body>'
                    . '<div style="padding:6mm 12mm 15mm 12mm;">' . $preview_html . '</div></body></html>';
            @endphp
            <div class="carousel-item {{ $selected_footer_template === $key ? 'active' : '' }}">
                <div class="pt-template-slide">
                    <iframe class="pt-template-frame-lg pt-template-frame-footer" srcdoc="{{ $preview_doc }}"></iframe>
                    <div class="pt-template-slide-footer">
                        <label class="pt-template-pick">
                            <input type="radio" name="footer_config[template]" value="{{ $key }}"
                                {{ $selected_footer_template === $key ? 'checked' : '' }}>
                            <strong>{{ $label }}</strong>
                        </label>
                        <span class="pt-template-position">{{ $loop->iteration }} / {{ $loop->count }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#footerTemplateCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#footerTemplateCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
    </button>
</div>

@foreach ($footer_sections as $key => $label)
    @php $s = $sections[$key] ?? []; @endphp
    <div class="row g-3 align-items-start mb-3 pb-3 border-bottom">
        <div class="col-md-3">
            <div class="form-check">
                <input type="hidden" name="footer_config[sections][{{ $key }}][visible]" value="0">
                <input class="form-check-input" type="checkbox" name="footer_config[sections][{{ $key }}][visible]"
                    value="1" {{ ($s['visible'] ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">{{ $label }}</label>
            </div>
        </div>
        <div class="col-md-9">
            <textarea class="form-control" rows="2" name="footer_config[sections][{{ $key }}][text]"
                placeholder="{{ __('settings.print_footer_section_placeholder', ['label' => $label]) }}">{{ $s['text'] ?? '' }}</textarea>
        </div>
    </div>
@endforeach

<hr>
<div class="row g-3">
    <div class="col-md-3">
        <div class="form-check">
            <input type="hidden" name="footer_config[signature_lines][visible]" value="0">
            <input class="form-check-input" type="checkbox" name="footer_config[signature_lines][visible]" value="1"
                {{ ($footer['signature_lines']['visible'] ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">{{ __('settings.print_signature_lines') }}</label>
        </div>
        <small class="text-muted">{{ __('settings.print_signature_lines_help') }}</small>
    </div>
    <div class="col-md-3">
        <div class="form-check">
            <input type="hidden" name="footer_config[printed_datetime][visible]" value="0">
            <input class="form-check-input" type="checkbox" name="footer_config[printed_datetime][visible]" value="1"
                {{ ($footer['printed_datetime']['visible'] ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">{{ __('settings.print_printed_datetime') }}</label>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-check">
            <input type="hidden" name="footer_config[page_numbers][visible]" value="0">
            <input class="form-check-input" type="checkbox" name="footer_config[page_numbers][visible]" value="1"
                {{ ($footer['page_numbers']['visible'] ?? false) ? 'checked' : '' }}>
            <label class="form-check-label">{{ __('settings.print_page_numbers') }}</label>
        </div>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-3">
        <div class="form-check">
            <input type="hidden" name="footer_config[authorized_by][visible]" value="0">
            <input class="form-check-input" type="checkbox" name="footer_config[authorized_by][visible]" value="1"
                {{ ($footer['authorized_by']['visible'] ?? false) ? 'checked' : '' }}>
            <label class="form-check-label">{{ __('settings.print_authorized_by') }}</label>
        </div>
        <input type="text" class="form-control form-control-sm mt-1" name="footer_config[authorized_by][label]"
            value="{{ $footer['authorized_by']['label'] ?? __('settings.print_authorized_by_default') }}">
    </div>
    <div class="col-md-3">
        <div class="form-check">
            <input type="hidden" name="footer_config[received_by][visible]" value="0">
            <input class="form-check-input" type="checkbox" name="footer_config[received_by][visible]" value="1"
                {{ ($footer['received_by']['visible'] ?? false) ? 'checked' : '' }}>
            <label class="form-check-label">{{ __('settings.print_received_by') }}</label>
        </div>
        <input type="text" class="form-control form-control-sm mt-1" name="footer_config[received_by][label]"
            value="{{ $footer['received_by']['label'] ?? __('settings.print_received_by_default') }}">
    </div>
</div>
