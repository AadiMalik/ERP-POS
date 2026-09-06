@php
    // $thermal_print_setting is null when a branch scope is selected that
    // has no override saved yet - falls back to the same shipped defaults a
    // brand-new business default row would use, so the form always renders
    // a sensible starting point regardless of scope.
    $field_config = optional($thermal_print_setting)->field_config ?? config('thermal_print_defaults.field_config');
    $footer_config = optional($thermal_print_setting)->footer_config ?? config('thermal_print_defaults.footer_config');

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

@php
    $thermal_current_branch_name = $thermal_branch_id
        ? optional($thermal_branches->firstWhere('branch_id', $thermal_branch_id))->name
        : null;
@endphp

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('settings.thermal_configuring') }}</label>
        <select class="form-select select2" id="thermalScopeSelect">
            <option value="">{{ __('settings.thermal_business_default') }}</option>
            @foreach ($thermal_branches as $branch)
                <option value="{{ $branch->branch_id }}" {{ $thermal_branch_id === $branch->branch_id ? 'selected' : '' }}>
                    {{ __('settings.thermal_branch_option', ['name' => $branch->name]) }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">
            @if ($thermal_branch_id)
                {{ __('settings.thermal_editing_branch_override', ['name' => $thermal_current_branch_name]) }}
                {{ $thermal_print_setting ? '' : __('settings.thermal_no_override_yet') }}
            @else
                {{ __('settings.thermal_editing_business_default') }}
            @endif
        </small>
    </div>
</div>

<form id="thermalPrintSettingForm">
    @csrf
    <input type="hidden" name="branch_id" id="thermal_branch_id_input" value="{{ $thermal_branch_id }}">
    <h4>{{ __('settings.thermal_title') }}</h4>
    <p class="text-muted">{{ __('settings.thermal_description') }}</p>
    <hr>

    <div class="row g-4">
        <div class="col-md-7">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_enabled" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="thermal_is_enabled"
                            name="is_enabled" value="1"
                            {{ !empty(optional($thermal_print_setting)->is_enabled) ? 'checked' : '' }}>
                        <label class="form-check-label" for="thermal_is_enabled">
                            {{ __('settings.thermal_enable') }}
                        </label>
                    </div>
                    <small class="text-muted">{{ __('settings.thermal_enable_help') }}</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('settings.thermal_paper_width') }}</label>
                    <select class="form-select select2" name="paper_width_mm">
                        <option value="80" {{ (optional($thermal_print_setting)->paper_width_mm ?? 80) == 80 ? 'selected' : '' }}>80mm</option>
                        <option value="58" {{ (optional($thermal_print_setting)->paper_width_mm ?? 80) == 58 ? 'selected' : '' }}>58mm</option>
                    </select>
                </div>
            </div>

            <hr>

            <div class="thermal-field-group">
                <h6>{{ __('settings.thermal_group_business') }}</h6>
                <div class="row g-2">
                    @foreach ([
        'branch_logo' => __('settings.thermal_field_branch_logo'),
        'branch_name' => __('settings.thermal_field_branch_name'),
        'email' => __('settings.thermal_field_email'),
        'phone' => __('settings.thermal_field_phone'),
        'address' => __('settings.thermal_field_address'),
        'business_ntn' => __('settings.thermal_field_business_ntn'),
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
                <h6>{{ __('settings.thermal_group_order') }}</h6>
                <div class="row g-2">
                    @foreach ([
        'customer_name' => __('settings.thermal_field_customer_name'),
        'order_type' => __('settings.thermal_field_order_type'),
        'order_no' => __('settings.thermal_field_order_no'),
        'date_time' => __('settings.thermal_field_date_time'),
        'order_source' => __('settings.thermal_field_order_source'),
        'order_taker_name' => __('settings.thermal_field_order_taker_name'),
        'sale_type' => __('settings.thermal_field_sale_type'),
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
                <h6>{{ __('settings.thermal_group_item_columns') }}</h6>
                <p class="text-muted mb-2" style="font-size: 12px;">{{ __('settings.thermal_item_columns_help') }}</p>
                <div class="row g-2">
                    @foreach ([
        'quantity' => __('settings.thermal_field_quantity'),
        'unit' => __('settings.thermal_field_unit'),
        'unit_price' => __('settings.thermal_field_unit_price'),
        'line_total' => __('settings.thermal_field_line_total'),
        'item_sale_type' => __('settings.thermal_field_item_sale_type'),
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
                <h6>{{ __('settings.thermal_group_totals') }}</h6>
                <div class="row g-2">
                    @foreach ([
        'subtotal' => __('settings.thermal_field_subtotal'),
        'discount' => __('settings.thermal_field_discount'),
        'tax' => __('settings.thermal_field_tax'),
        'voucher' => __('settings.thermal_field_voucher'),
        'total' => __('settings.thermal_field_total'),
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
                <h6>{{ __('settings.thermal_group_payment') }}</h6>
                <div class="row g-2">
                    @foreach ([
        'paid_amount' => __('settings.thermal_field_paid_amount'),
        'due_amount' => __('settings.thermal_field_due_amount'),
        'payment_status' => __('settings.thermal_field_payment_status'),
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
                <h6>{{ __('settings.thermal_group_footer') }}</h6>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="field_config[thank_you_note]" value="0">
                            <input class="form-check-input" type="checkbox" name="field_config[thank_you_note]"
                                value="1" {{ $field_check('thank_you_note') }}>
                            <label class="form-check-label">{{ __('settings.thermal_field_thank_you_note') }}</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="field_config[qr_code]" value="0">
                            <input class="form-check-input" type="checkbox" name="field_config[qr_code]" value="1"
                                {{ $field_check('qr_code') }}>
                            <label class="form-check-label">{{ __('settings.thermal_field_qr_code') }}</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="field_config[powered_by_smart_mart]" value="0">
                            <input class="form-check-input" type="checkbox"
                                name="field_config[powered_by_smart_mart]" value="1"
                                {{ $field_check('powered_by_smart_mart') }}>
                            <label class="form-check-label">{{ __('settings.thermal_field_powered_by') }}</label>
                        </div>
                    </div>
                </div>

                <div class="row g-2 mt-2">
                    <div class="col-md-12">
                        <label class="form-label">{{ __('settings.thermal_thank_you_note_text') }}</label>
                        <textarea class="form-control" name="footer_config[thank_you_note]" rows="2">{{ $footer_config['thank_you_note'] ?? '' }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('settings.thermal_qr_code_content') }}</label>
                        <select class="form-select select2" name="footer_config[qr_data_source]">
                            <option value="order_no" {{ ($footer_config['qr_data_source'] ?? 'order_no') === 'order_no' ? 'selected' : '' }}>{{ __('settings.thermal_qr_order_no') }}</option>
                            <option value="order_url" {{ ($footer_config['qr_data_source'] ?? '') === 'order_url' ? 'selected' : '' }}>{{ __('settings.thermal_qr_order_url') }}</option>
                            <option value="custom" {{ ($footer_config['qr_data_source'] ?? '') === 'custom' ? 'selected' : '' }}>{{ __('settings.thermal_qr_custom') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('settings.thermal_custom_qr_text') }}</label>
                        <input type="text" class="form-control" name="footer_config[qr_custom_text]"
                            value="{{ $footer_config['qr_custom_text'] ?? '' }}">
                    </div>
                </div>
            </div>

            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#thermalPrintSettingForm','{{ route('thermal_print.update') }}')">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </div>

        <div class="col-md-5">
            <div class="thermal-preview-wrap">
                <h6>{{ __('settings.thermal_live_preview') }}</h6>
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
                data: buildSettingFormData(form),
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

            // Restore the Thermal Print tab after a scope-switch reload -
            // this page has no other hash-based tab restoration, so this is
            // the one tab that needs it (every other tab's settings don't
            // require a full reload to switch scope).
            if (window.location.search.indexOf('thermal_branch_id') !== -1) {
                var tabEl = document.querySelector('[data-bs-target="#thermal_print"]');
                if (tabEl && window.bootstrap) {
                    new bootstrap.Tab(tabEl).show();
                }
            }
        });

        $(document).on('change', '#thermalScopeSelect', function() {
            var branchId = $(this).val();
            var url = new URL(window.location.href);
            if (branchId) {
                url.searchParams.set('thermal_branch_id', branchId);
            } else {
                url.searchParams.delete('thermal_branch_id');
            }
            window.location.href = url.toString();
        });
    </script>
@endonce
