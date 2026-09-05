<form id="barcodeSettingForm">
    @csrf
    <div class="row">
        <div class="col-md-12">
            <h4>{{ __('settings.barcode_title') }}</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="enable_barcode" id="barcode_enable_barcode"
                    {{ $barcode_setting->enable_barcode ? 'checked' : '' }}>
                <label>{{ __('settings.barcode_enable_barcode') }}</label>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="enable_qr_code" id="barcode_enable_qr_code"
                    {{ $barcode_setting->enable_qr_code ? 'checked' : '' }}>
                <label>{{ __('settings.barcode_enable_qr_code') }}</label>
            </div>
        </div>

        <div class="col-md-6 mb-3 barcode-config-field">
            <label>{{ __('settings.barcode_format') }}<span class="text-danger">*</span></label>
            <select class="form-select select2" name="barcode_type">
                @foreach ($barcode_types as $key => $label)
                    <option value="{{ $key }}" {{ $barcode_setting->barcode_type == $key ? 'selected' : '' }}>
                        {{ $label }}</option>
                @endforeach
            </select>
            <small class="text-muted">{{ __('settings.barcode_format_help') }}</small>
        </div>
        <div class="col-md-3 mb-3 barcode-config-field barcode-code128-field">
            <label>{{ __('settings.barcode_code128_length') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control" name="code128_length"
                value="{{ $barcode_setting->code128_length }}">
        </div>
        <div class="col-md-3 mb-3 barcode-config-field barcode-code128-field">
            <label>{{ __('settings.barcode_prefix') }}</label>
            <input type="text" class="form-control" name="barcode_prefix" maxlength="20"
                value="{{ $barcode_setting->barcode_prefix }}">
        </div>

        <div class="col-md-6 mb-3 qr-config-field">
            <label>{{ __('settings.barcode_qr_data_source') }}<span class="text-danger">*</span></label>
            <select class="form-select select2" name="qr_data_source">
                @foreach ($qr_data_sources as $key => $label)
                    <option value="{{ $key }}" {{ $barcode_setting->qr_data_source == $key ? 'selected' : '' }}>
                        {{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-3 qr-config-field">
            <label>{{ __('settings.barcode_qr_size_px') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control" name="qr_size_px"
                value="{{ $barcode_setting->qr_size_px }}">
        </div>
        <div class="col-md-3 mb-3 qr-config-field">
            <label>{{ __('settings.barcode_qr_error_correction') }}</label>
            <select class="form-select select2" name="qr_error_correction">
                @foreach ([
                    'L' => __('settings.barcode_qr_error_low'),
                    'M' => __('settings.barcode_qr_error_medium'),
                    'Q' => __('settings.barcode_qr_error_quartile'),
                    'H' => __('settings.barcode_qr_error_high'),
                ] as $key => $label)
                    <option value="{{ $key }}"
                        {{ $barcode_setting->qr_error_correction == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @php $label_config = $barcode_setting->label_config ?? []; @endphp
        <div class="col-md-12">
            <hr>
            <h5>{{ __('settings.barcode_label_design') }}</h5>
        </div>
        <div class="col-md-3 mb-3">
            <label>{{ __('settings.barcode_label_width_mm') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="label_config[width_mm]" value="{{ $label_config['width_mm'] ?? 40 }}">
        </div>
        <div class="col-md-3 mb-3">
            <label>{{ __('settings.barcode_label_height_mm') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="label_config[height_mm]" value="{{ $label_config['height_mm'] ?? 25 }}">
        </div>
        <div class="col-md-3 mb-3">
            <label>{{ __('settings.barcode_label_columns_per_row') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="label_config[columns_per_row]" value="{{ $label_config['columns_per_row'] ?? 3 }}">
        </div>
        <div class="col-md-3 mb-3">
            <label>{{ __('settings.barcode_label_spacing_mm') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="label_config[spacing_mm]" value="{{ $label_config['spacing_mm'] ?? 2 }}">
        </div>
        <div class="col-md-3 mb-3">
            <label>{{ __('settings.barcode_label_font_size_pt') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="label_config[font_size_pt]" value="{{ $label_config['font_size_pt'] ?? 8 }}">
        </div>
        <div class="col-md-3 mb-3">
            <label>{{ __('settings.barcode_label_alignment') }}</label>
            <select class="form-select select2" name="label_config[alignment]">
                @foreach ([
                    'left' => __('settings.barcode_align_left'),
                    'center' => __('settings.barcode_align_center'),
                    'right' => __('settings.barcode_align_right'),
                ] as $key => $label)
                    <option value="{{ $key }}"
                        {{ ($label_config['alignment'] ?? 'center') == $key ? 'selected' : '' }}>{{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        @foreach ([
        'show_product_name' => __('settings.barcode_show_product_name'),
        'show_variation_name' => __('settings.barcode_show_variation_name'),
        'show_sku' => __('settings.barcode_show_sku'),
        'show_barcode' => __('settings.barcode_show_barcode'),
        'show_barcode_value_text' => __('settings.barcode_show_barcode_value_text'),
        'show_qr_code' => __('settings.barcode_show_qr_code'),
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
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</form>
