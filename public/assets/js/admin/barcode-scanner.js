// ======================================================
// REUSABLE BARCODE / QR SCANNER
// Wires any "Scan" button (data-target-input attribute) to the shared
// scan modal, fills the target input on a successful decode, and exposes
// resolveBarcodeLookup() so any screen can resolve a code to a product/variation
// without duplicating the AJAX call.
// ======================================================

let barcodeScannerInstance = null;
let barcodeScanTargetInput = null;

function initBarcodeScanner() {
    const modalEl = document.getElementById('barcodeScanModal');

    if (!modalEl || typeof Html5QrcodeScanner === 'undefined') {
        return;
    }

    $(document).on('click', '.btn-scan-barcode', function () {
        barcodeScanTargetInput = $(this).data('target-input');

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        barcodeScannerInstance = new Html5QrcodeScanner('barcodeScanReader', {
            fps: 10,
            qrbox: 250,
        }, false);

        barcodeScannerInstance.render(onBarcodeScanSuccess, function () {
            // decode attempt failed for this frame - ignore, scanner keeps trying
        });
    });

    $(modalEl).on('hidden.bs.modal', stopBarcodeScanner);
}

function onBarcodeScanSuccess(decodedText) {
    if (barcodeScanTargetInput) {
        $(barcodeScanTargetInput).val(decodedText).trigger('change');
    }

    const modalEl = document.getElementById('barcodeScanModal');
    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
}

function stopBarcodeScanner() {
    if (barcodeScannerInstance) {
        barcodeScannerInstance.clear().catch(function () {});
        barcodeScannerInstance = null;
    }
}

// Resolve a scanned/typed code to its product + variation. Reused by every
// screen that wants to act on a scan instead of each writing its own AJAX call.
function resolveBarcodeLookup(code, onFound, onNotFound) {
    ajaxRequest({
        url: url_local + '/admin/barcode/lookup?code=' + encodeURIComponent(code),
        method: 'GET',
    }).then(function (response) {
        if (typeof onFound === 'function') {
            onFound(response.Data);
        }
    }).catch(function (err) {
        if (typeof onNotFound === 'function') {
            onNotFound(err);
        } else {
            errorMessage(err.Message || 'No product found for this code.');
        }
    });
}

$(document).ready(function () {
    initBarcodeScanner();
});
