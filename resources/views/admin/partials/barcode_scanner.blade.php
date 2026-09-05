{{--
    Reusable barcode/QR scan trigger. Include anywhere a barcode input exists:
        @include('admin.partials.barcode_scanner', ['targetInputId' => '#someInput'])
    On a successful scan, barcode-scanner.js fills the target input and fires a
    native 'change' event on it, so existing change handlers react normally.
--}}
<button type="button" class="btn btn-outline-secondary btn-scan-barcode"
    data-target-input="{{ $targetInputId ?? '#barcode_input' }}" title="Scan barcode or QR code">
    <i class="fa fa-camera"></i> Scan
</button>

@once
    <div class="modal fade" id="barcodeScanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Scan Barcode / QR Code') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="barcodeScanReader"></div>
                </div>
            </div>
        </div>
    </div>
@endonce
