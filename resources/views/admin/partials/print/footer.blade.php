{{--
    Shared print signature/footer partial.
    Expects: $signatories (array of labels, module-supplied)
    Optional: $print_config (App\Support\Print\PrintConfig)
--}}
@php
    $pc = $print_config ?? new \App\Support\Print\PrintConfig(config('print_defaults'));

    $sections = [
        'footer_notes' => null,
        'thank_you_message' => null,
        'terms_and_conditions' => 'Terms & Conditions',
        'return_policy' => 'Return Policy',
        'payment_instructions' => 'Payment Instructions',
        'bank_details' => 'Bank Details',
        'contact_info' => null,
        'website' => null,
        'social_links' => null,
        'confidential_notice' => null,
        'custom_text_block' => null,
    ];

    $labels = $pc->signatureLabels($signatories ?? []);
@endphp

@foreach ($sections as $key => $heading)
    @if ($pc->isVisible('footer', $key) && !empty($pc->footerText($key)))
        <div class="print-footer-section">
            @if ($heading)
                <strong>{{ $heading }}:</strong>
            @endif
            {!! nl2br(e($pc->footerText($key))) !!}
        </div>
    @endif
@endforeach

<div class="print-footer">
    @if ($pc->footerMeta('signature_lines.visible', true))
        <div class="print-signatures">
            @foreach ($labels as $signatory)
                <div class="signature-box">
                    <div class="signature-line"></div>
                    {{ $signatory }}
                </div>
            @endforeach

            @if ($pc->footerMeta('authorized_by.visible', false))
                <div class="signature-box">
                    <div class="signature-line"></div>
                    {{ $pc->footerMeta('authorized_by.label', 'Authorized By') }}
                </div>
            @endif

            @if ($pc->footerMeta('received_by.visible', false))
                <div class="signature-box">
                    <div class="signature-line"></div>
                    {{ $pc->footerMeta('received_by.label', 'Received By') }}
                </div>
            @endif
        </div>
    @endif

    @if ($pc->footerMeta('printed_datetime.visible', true))
        <p class="generated-at">Generated on {{ localDateTime(now()) }}</p>
    @endif

    @if ($pc->footerMeta('page_numbers.visible', false))
        <p class="generated-at print-page-number"></p>
    @endif
</div>
