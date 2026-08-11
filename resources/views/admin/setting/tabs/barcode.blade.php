<form id="barcodeSettingForm">
    @csrf
    <div class="row">
        <div class="col-md-12">
            <h4>Barcode &amp; QR Code Setting</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="enable_barcode" id="barcode_enable_barcode"
                    {{ $barcode_setting->enable_barcode ? 'checked' : '' }}>
                <label>Enable Barcode</label>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="enable_qr_code" id="barcode_enable_qr_code"
                    {{ $barcode_setting->enable_qr_code ? 'checked' : '' }}>
                <label>Enable QR Code</label>
            </div>
        </div>

        <div class="col-md-6 mb-3 barcode-config-field">
            <label>Barcode Format<span class="text-danger">*</span></label>
            <select class="form-select select2" name="barcode_type">
                @foreach ($barcode_types as $key => $label)
                    <option value="{{ $key }}" {{ $barcode_setting->barcode_type == $key ? 'selected' : '' }}>
                        {{ $label }}</option>
                @endforeach
            </select>
            <small class="text-muted">Used only when a manufacturer barcode isn't provided.</small>
        </div>
        <div class="col-md-3 mb-3 barcode-config-field barcode-code128-field">
            <label>Code 128 Length</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control" name="code128_length"
                value="{{ $barcode_setting->code128_length }}">
        </div>
        <div class="col-md-3 mb-3 barcode-config-field barcode-code128-field">
            <label>Barcode Prefix</label>
            <input type="text" class="form-control" name="barcode_prefix" maxlength="20"
                value="{{ $barcode_setting->barcode_prefix }}">
        </div>

        <div class="col-md-6 mb-3 qr-config-field">
            <label>QR Data Source<span class="text-danger">*</span></label>
            <select class="form-select select2" name="qr_data_source">
                @foreach ($qr_data_sources as $key => $label)
                    <option value="{{ $key }}" {{ $barcode_setting->qr_data_source == $key ? 'selected' : '' }}>
                        {{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-3 qr-config-field">
            <label>QR Size (px)</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control" name="qr_size_px"
                value="{{ $barcode_setting->qr_size_px }}">
        </div>
        <div class="col-md-3 mb-3 qr-config-field">
            <label>QR Error Correction</label>
            <select class="form-select select2" name="qr_error_correction">
                @foreach (['L' => 'Low', 'M' => 'Medium', 'Q' => 'Quartile', 'H' => 'High'] as $key => $label)
                    <option value="{{ $key }}"
                        {{ $barcode_setting->qr_error_correction == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @php $label_config = $barcode_setting->label_config ?? []; @endphp
        <div class="col-md-12">
            <hr>
            <h5>Label Design</h5>
        </div>
        <div class="col-md-3 mb-3">
            <label>Width (mm)</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="label_config[width_mm]" value="{{ $label_config['width_mm'] ?? 40 }}">
        </div>
        <div class="col-md-3 mb-3">
            <label>Height (mm)</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="label_config[height_mm]" value="{{ $label_config['height_mm'] ?? 25 }}">
        </div>
        <div class="col-md-3 mb-3">
            <label>Columns Per Row</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="label_config[columns_per_row]" value="{{ $label_config['columns_per_row'] ?? 3 }}">
        </div>
        <div class="col-md-3 mb-3">
            <label>Spacing (mm)</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="label_config[spacing_mm]" value="{{ $label_config['spacing_mm'] ?? 2 }}">
        </div>
        <div class="col-md-3 mb-3">
            <label>Font Size (pt)</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="label_config[font_size_pt]" value="{{ $label_config['font_size_pt'] ?? 8 }}">
        </div>
        <div class="col-md-3 mb-3">
            <label>Alignment</label>
            <select class="form-select select2" name="label_config[alignment]">
                @foreach (['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $key => $label)
                    <option value="{{ $key }}"
                        {{ ($label_config['alignment'] ?? 'center') == $key ? 'selected' : '' }}>{{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        @foreach ([
        'show_product_name' => 'Show Product Name',
        'show_variation_name' => 'Show Variation Name',
        'show_sku' => 'Show SKU',
        'show_barcode' => 'Show Barcode Image',
        'show_barcode_value_text' => 'Show Barcode Value Text',
        'show_qr_code' => 'Show QR Code',
    ] as $field => $label)
            <div class="col-md-4 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="label_config[{{ $field }}]"
                        {{ ($label_config[$field] ?? false) ? 'checked' : '' }}>
                    <label>{{ $label }}</label>
                </div>
            </div>
        @endforeach

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary" id="btnBarcodeSetting"
                    onclick="saveSetting('#barcodeSettingForm','{{ url('admin/setting/barcode') }}')">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>
