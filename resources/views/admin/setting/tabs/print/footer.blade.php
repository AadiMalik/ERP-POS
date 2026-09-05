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
@endphp

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
