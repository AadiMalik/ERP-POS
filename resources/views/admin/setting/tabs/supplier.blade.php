<form id="supplierSettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>{{ __('settings.supplier_title') }}</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.supplier_code_prefix') }}</label>
            <input type="text" class="form-control" name="supplier_code_prefix"
                value="{{ $supplier_setting->supplier_code_prefix }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.enable_credit_limit') }}</label>
            <select class="form-select select2" name="supplier_enable_credit_limit">
                <option value="1" {{ $supplier_setting->enable_credit_limit == 1 ? 'selected' : '' }}>
                    {{ __('common.yes') }}
                </option>
                <option value="0" {{ $supplier_setting->enable_credit_limit == 0 ? 'selected' : '' }}>
                    {{ __('common.no') }}
                </option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.default_credit_limit') }}</label>
            <input type="text" class="form-control" name="supplier_credit_limit" onkeypress="return isNumberKey(event)"
                value="{{ $supplier_setting->credit_limit }}">
            <small class="text-muted">
                {{ __('settings.supplier_credit_limit_help') }}
            </small>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.default_payment_days') }}</label>
            <input type="text" class="form-control" name="default_payment_days"
                onkeypress="return isNumberKey(event)" value="{{ $supplier_setting->default_payment_days }}">
            <small class="text-muted">
                {{ __('settings.default_payment_days_help') }}
            </small>
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#supplierSettingForm','{{ url('admin/setting/supplier') }}')">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</form>
