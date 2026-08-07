{{--
    Shared print signature/footer partial.
    Expects: $signatories (array of labels, module-supplied)
--}}
<div class="print-footer">
    <div class="print-signatures">
        @foreach ($signatories ?? [] as $signatory)
            <div class="signature-box">
                <div class="signature-line"></div>
                {{ $signatory }}
            </div>
        @endforeach
    </div>

    <p class="generated-at">Generated on {{ localDateTime(now()) }}</p>
</div>
