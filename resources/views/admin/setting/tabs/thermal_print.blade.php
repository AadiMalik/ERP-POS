@php
    $field_config = $thermal_print_setting->field_config ?? config('thermal_print_defaults.field_config');
    $footer_config = $thermal_print_setting->footer_config ?? config('thermal_print_defaults.footer_config');

    $field_check = function ($key) use ($field_config) {
        return !empty($field_config[$key] ?? false) ? 'checked' : '';
    };
@endphp

<style>
    .thermal-preview-wrap {
        position: sticky;
        top: 10px;
    }

    .thermal-preview-frame {
        width: 100%;
        height: 720px;
        border: 1px solid #d9d9d9;
        border-radius: 4px;
        background: #e9ebee;
    }

    .thermal-field-group {
        margin-bottom: 14px;
    }

    .thermal-field-group h6 {
        margin-bottom: 8px;
        font-weight: 700;
    }
</style>

<form id="thermalPrintSettingForm">
    @csrf
    <h4>Thermal Printer Invoice Settings</h4>
    <p class="text-muted">Configure a compact, thermal-width receipt layout for POS/order printing, and choose which
        fields appear on it. The preview on the right always reflects the exact layout that will print.</p>
    <hr>

    <div class="row g-4">
        <div class="col-md-7">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_enabled" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="thermal_is_enabled"
                            name="is_enabled" value="1"
                            {{ !empty($thermal_print_setting->is_enabled) ? 'checked' : '' }}>
                        <label class="form-check-label" for="thermal_is_enabled">
                            Enable Thermal Receipt Printing
                        </label>
                    </div>
                    <small class="text-muted">When enabled, the Print action (and POS auto-print) uses this thermal
                        layout instead of the standard A4 invoice.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Paper Width</label>
                    <select class="form-select select2" name="paper_width_mm">
                        <option value="80" {{ ($thermal_print_setting->paper_width_mm ?? 80) == 80 ? 'selected' : '' }}>80mm</option>
                        <option value="58" {{ ($thermal_print_setting->paper_width_mm ?? 80) == 58 ? 'selected' : '' }}>58mm</option>
                    </select>
                </div>
            </div>

            <hr>

            <div class="thermal-field-group">
                <h6>Business / Branch Info</h6>
                <div class="row g-2">
                    @foreach ([
        'branch_logo' => 'Branch Logo',
        'branch_name' => 'Branch Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'address' => 'Address',
        'business_ntn' => 'Business NTN',
    ] as $key => $label)
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="hidden" name="field_config[{{ $key }}]" value="0">
                                <input class="form-check-input" type="checkbox" name="field_config[{{ $key }}]"
                                    value="1" {{ $field_check($key) }}>
                                <label class="form-check-label">{{ $label }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="thermal-field-group">
                <h6>Order Info</h6>
                <div class="row g-2">
                    @foreach ([
        'customer_name' => 'Customer Name',
        'order_type' => 'Order Type',
        'order_no' => 'Order No',
        'date_time' => 'Local Date & Time',
        'order_source' => 'Order Source',
        'order_taker_name' => 'Order Taker Name',
    ] as $key => $label)
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="hidden" name="field_config[{{ $key }}]" value="0">
                                <input class="form-check-input" type="checkbox" name="field_config[{{ $key }}]"
                                    value="1" {{ $field_check($key) }}>
                                <label class="form-check-label">{{ $label }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="thermal-field-group">
                <h6>Item Table Columns</h6>
                <p class="text-muted mb-2" style="font-size: 12px;">Product name is always shown; these columns are
                    optional.</p>
                <div class="row g-2">
                    @foreach ([
        'quantity' => 'Quantity',
        'unit' => 'Unit',
        'unit_price' => 'Unit Price',
        'line_total' => 'Line Total',
    ] as $key => $label)
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="hidden" name="field_config[{{ $key }}]" value="0">
                                <input class="form-check-input" type="checkbox" name="field_config[{{ $key }}]"
                                    value="1" {{ $field_check($key) }}>
                                <label class="form-check-label">{{ $label }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="thermal-field-group">
                <h6>Totals</h6>
                <div class="row g-2">
                    @foreach ([
        'subtotal' => 'Subtotal',
        'discount' => 'Discount (% and Amount)',
        'tax' => 'Tax (% and Amount)',
        'voucher' => 'Voucher Details',
        'total' => 'Total',
    ] as $key => $label)
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="hidden" name="field_config[{{ $key }}]" value="0">
                                <input class="form-check-input" type="checkbox" name="field_config[{{ $key }}]"
                                    value="1" {{ $field_check($key) }}>
                                <label class="form-check-label">{{ $label }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="thermal-field-group">
                <h6>Payment</h6>
                <div class="row g-2">
                    @foreach ([
        'paid_amount' => 'Paid Amount',
        'due_amount' => 'Remaining / Due Amount',
        'payment_status' => 'Payment / Paid Status',
    ] as $key => $label)
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="hidden" name="field_config[{{ $key }}]" value="0">
                                <input class="form-check-input" type="checkbox" name="field_config[{{ $key }}]"
                                    value="1" {{ $field_check($key) }}>
                                <label class="form-check-label">{{ $label }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="thermal-field-group">
                <h6>Footer</h6>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="field_config[thank_you_note]" value="0">
                            <input class="form-check-input" type="checkbox" name="field_config[thank_you_note]"
                                value="1" {{ $field_check('thank_you_note') }}>
                            <label class="form-check-label">Thank You Note</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="field_config[qr_code]" value="0">
                            <input class="form-check-input" type="checkbox" name="field_config[qr_code]" value="1"
                                {{ $field_check('qr_code') }}>
                            <label class="form-check-label">QR Code</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="field_config[powered_by_smart_mart]" value="0">
                            <input class="form-check-input" type="checkbox"
                                name="field_config[powered_by_smart_mart]" value="1"
                                {{ $field_check('powered_by_smart_mart') }}>
                            <label class="form-check-label">"Powered by Smart Mart ERP" Credit</label>
                        </div>
                    </div>
                </div>

                <div class="row g-2 mt-2">
                    <div class="col-md-12">
                        <label class="form-label">Thank You Note Text</label>
                        <textarea class="form-control" name="footer_config[thank_you_note]" rows="2">{{ $footer_config['thank_you_note'] ?? '' }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">QR Code Content</label>
                        <select class="form-select select2" name="footer_config[qr_data_source]">
                            <option value="order_no" {{ ($footer_config['qr_data_source'] ?? 'order_no') === 'order_no' ? 'selected' : '' }}>Order No</option>
                            <option value="order_url" {{ ($footer_config['qr_data_source'] ?? '') === 'order_url' ? 'selected' : '' }}>Order Print URL</option>
                            <option value="custom" {{ ($footer_config['qr_data_source'] ?? '') === 'custom' ? 'selected' : '' }}>Custom Text</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Custom QR Text</label>
                        <input type="text" class="form-control" name="footer_config[qr_custom_text]"
                            value="{{ $footer_config['qr_custom_text'] ?? '' }}">
                    </div>
                </div>
            </div>

            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#thermalPrintSettingForm','{{ route('thermal_print.update') }}')">
                    Save Changes
                </button>
            </div>
        </div>

        <div class="col-md-5">
            <div class="thermal-preview-wrap">
                <h6>Live Preview</h6>
                <iframe id="thermalPreviewFrame" class="thermal-preview-frame"></iframe>
            </div>
        </div>
    </div>
</form>

@once
    <script>
        let thermalPreviewTimer = null;

        function refreshThermalPreview() {
            let form = document.getElementById('thermalPrintSettingForm');
            if (!form) return;

            ajaxRequest({
                url: '{{ route('thermal_print.preview') }}',
                method: 'POST',
                data: new FormData(form),
                isFormData: true
            }).then(res => {
                let frame = document.getElementById('thermalPreviewFrame');
                if (frame) frame.srcdoc = res.Data;
            }).catch(() => {});
        }

        $(document).on('input change', '#thermalPrintSettingForm', function() {
            clearTimeout(thermalPreviewTimer);
            thermalPreviewTimer = setTimeout(refreshThermalPreview, 400);
        });

        $(document).ready(function() {
            refreshThermalPreview();
        });
    </script>
@endonce
