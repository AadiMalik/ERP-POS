@php
    $footer_sections = [
        'footer_notes' => 'Footer Notes',
        'thank_you_message' => 'Thank You Message',
        'terms_and_conditions' => 'Terms &amp; Conditions',
        'return_policy' => 'Return Policy',
        'payment_instructions' => 'Payment Instructions',
        'bank_details' => 'Bank Details',
        'contact_info' => 'Company Contact Information',
        'website' => 'Website',
        'social_links' => 'Social Media Links',
        'confidential_notice' => 'Confidential Notice',
        'custom_text_block' => 'Custom Text',
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
                <label class="form-check-label">{!! $label !!}</label>
            </div>
        </div>
        <div class="col-md-9">
            <textarea class="form-control" rows="2" name="footer_config[sections][{{ $key }}][text]"
                placeholder="{{ strip_tags($label) }} text...">{{ $s['text'] ?? '' }}</textarea>
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
            <label class="form-check-label">Signature Lines</label>
        </div>
        <small class="text-muted">Leave labels blank to use each document's own default signatories.</small>
    </div>
    <div class="col-md-3">
        <div class="form-check">
            <input type="hidden" name="footer_config[printed_datetime][visible]" value="0">
            <input class="form-check-input" type="checkbox" name="footer_config[printed_datetime][visible]" value="1"
                {{ ($footer['printed_datetime']['visible'] ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Printed Date &amp; Time</label>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-check">
            <input type="hidden" name="footer_config[page_numbers][visible]" value="0">
            <input class="form-check-input" type="checkbox" name="footer_config[page_numbers][visible]" value="1"
                {{ ($footer['page_numbers']['visible'] ?? false) ? 'checked' : '' }}>
            <label class="form-check-label">Page Numbers</label>
        </div>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-3">
        <div class="form-check">
            <input type="hidden" name="footer_config[authorized_by][visible]" value="0">
            <input class="form-check-input" type="checkbox" name="footer_config[authorized_by][visible]" value="1"
                {{ ($footer['authorized_by']['visible'] ?? false) ? 'checked' : '' }}>
            <label class="form-check-label">Authorized By signature</label>
        </div>
        <input type="text" class="form-control form-control-sm mt-1" name="footer_config[authorized_by][label]"
            value="{{ $footer['authorized_by']['label'] ?? 'Authorized By' }}">
    </div>
    <div class="col-md-3">
        <div class="form-check">
            <input type="hidden" name="footer_config[received_by][visible]" value="0">
            <input class="form-check-input" type="checkbox" name="footer_config[received_by][visible]" value="1"
                {{ ($footer['received_by']['visible'] ?? false) ? 'checked' : '' }}>
            <label class="form-check-label">Received By signature</label>
        </div>
        <input type="text" class="form-control form-control-sm mt-1" name="footer_config[received_by][label]"
            value="{{ $footer['received_by']['label'] ?? 'Received By' }}">
    </div>
</div>
