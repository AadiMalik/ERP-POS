@php
    $header_fields = [
        'logo' => ['label' => 'Company / Business Logo', 'align' => 'left'],
        'company_name' => ['label' => 'Company Name', 'align' => 'left'],
        'branch_name' => ['label' => 'Branch Name', 'align' => 'left'],
        'address' => ['label' => 'Address', 'align' => 'left'],
        'phone' => ['label' => 'Phone Number', 'align' => 'left'],
        'email' => ['label' => 'Email', 'align' => 'left'],
        'website' => ['label' => 'Website', 'align' => 'left'],
        'ntn' => ['label' => 'NTN', 'align' => 'left'],
        'strn' => ['label' => 'STRN', 'align' => 'left'],
        'tax_reg_no' => ['label' => 'Tax Registration Number', 'align' => 'left'],
        'currency' => ['label' => 'Currency', 'align' => 'left'],
        'document_title' => ['label' => 'Document Title', 'align' => 'right'],
        'document_no' => ['label' => 'Document Number', 'align' => 'right'],
        'voucher_no' => ['label' => 'Voucher Number', 'align' => 'right'],
        'reference_no' => ['label' => 'Reference Number', 'align' => 'right'],
        'date' => ['label' => 'Date', 'align' => 'right'],
        'time' => ['label' => 'Time', 'align' => 'right'],
        'printed_by' => ['label' => 'Printed By', 'align' => 'right'],
        'printed_on' => ['label' => 'Printed On', 'align' => 'right'],
        'status_badge' => ['label' => 'Status Badge', 'align' => 'right'],
        'posting_status_badge' => ['label' => 'Posting Status (Posted/Unposted)', 'align' => 'right'],
        'approval_status' => ['label' => 'Approval Status', 'align' => 'right'],
        'qr_code' => ['label' => 'QR Code', 'align' => 'right'],
        'barcode' => ['label' => 'Barcode', 'align' => 'right'],
    ];
    $watermark = $header['watermark'] ?? [];
@endphp

<div class="table-responsive">
    <table class="table pt-field-table">
        <thead>
            <tr>
                <th>Field</th>
                <th>Visible</th>
                <th>Alignment</th>
                <th>Order</th>
                <th>Font Size</th>
                <th>Font Weight</th>
                <th>Color</th>
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
                            @foreach (['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $val => $label)
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
                            @foreach (['normal' => 'Normal', 'bold' => 'Bold', '800' => 'Extra Bold'] as $val => $label)
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
<h6>Watermark</h6>
<div class="row g-3">
    <div class="col-md-2">
        <div class="form-check">
            <input type="hidden" name="header_config[watermark][visible]" value="0">
            <input class="form-check-input" type="checkbox" name="header_config[watermark][visible]" value="1"
                {{ ($watermark['visible'] ?? false) ? 'checked' : '' }}>
            <label class="form-check-label">Visible</label>
        </div>
    </div>
    <div class="col-md-3">
        <label class="form-label">Text</label>
        <input type="text" class="form-control" name="header_config[watermark][text]"
            value="{{ $watermark['text'] ?? 'DRAFT' }}" placeholder="e.g. DRAFT, COPY, ORIGINAL, CANCELLED">
    </div>
    <div class="col-md-2">
        <label class="form-label">Color</label>
        <input type="color" class="form-control form-control-color"
            value="{{ $watermark['color'] ?? '#cccccc' }}" name="header_config[watermark][color]">
    </div>
    <div class="col-md-2">
        <label class="form-label">Opacity</label>
        <input type="number" step="0.05" min="0" max="1" class="form-control"
            name="header_config[watermark][opacity]" value="{{ $watermark['opacity'] ?? 0.15 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Font Size</label>
        <input type="number" class="form-control" name="header_config[watermark][font_size]"
            value="{{ $watermark['font_size'] ?? 60 }}">
    </div>
    <div class="col-md-1">
        <label class="form-label">Rotate</label>
        <input type="number" class="form-control" name="header_config[watermark][rotation_deg]"
            value="{{ $watermark['rotation_deg'] ?? -30 }}">
    </div>
</div>
