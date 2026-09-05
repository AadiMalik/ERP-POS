<form id="customerSettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>{{ __('settings.customer_title') }}</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.customer_code_prefix') }}</label>
            <input type="text" class="form-control" name="customer_code_prefix"
                value="{{ $customer_setting->customer_code_prefix }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.enable_credit_limit') }}</label>
            <select class="form-select select2" name="customer_enable_credit_limit">
                <option value="1" {{ $customer_setting->enable_credit_limit == 1 ? 'selected' : '' }}>
                    {{ __('common.yes') }}
                </option>
                <option value="0" {{ $customer_setting->enable_credit_limit == 0 ? 'selected' : '' }}>
                    {{ __('common.no') }}
                </option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.default_credit_limit') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control" name="customer_credit_limit"
                value="{{ $customer_setting->credit_limit }}">
            <small class="text-muted">
                {{ __('settings.customer_credit_limit_help') }}
            </small>
        </div>

        <div class="col-md-12">
            <h4>{{ __('settings.loyalty_program_heading') }}</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.enable_loyalty_program') }}</label>
            <select class="form-select select2" name="loyalty_program">
                <option value="1" {{ $customer_setting->loyalty_program == 1 ? 'selected' : '' }}>
                    {{ __('common.yes') }}
                </option>
                <option value="0" {{ $customer_setting->loyalty_program == 0 ? 'selected' : '' }}>
                    {{ __('common.no') }}
                </option>
            </select>
            <small class="text-muted">
                {{ __('settings.loyalty_program_help') }}
            </small>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.loyalty_earning_mode') }}</label>
            <select class="form-select select2" name="loyalty_earning_mode">
                <option value="order" {{ $customer_setting->loyalty_earning_mode == 'order' ? 'selected' : '' }}>
                    {{ __('settings.loyalty_earning_order') }}
                </option>
                <option value="product" {{ $customer_setting->loyalty_earning_mode == 'product' ? 'selected' : '' }}>
                    {{ __('settings.loyalty_earning_product') }}
                </option>
            </select>
            <small class="text-muted">
                {{ __('settings.loyalty_earning_mode_help') }}
            </small>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.loyalty_every_amount') }}</label>
            <div class="input-group">
                <span class="input-group-text">Rs</span>
                <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                    name="loyalty_every_amount" value="{{ $customer_setting->loyalty_every_amount }}">
            </div>
            <small class="text-muted">
                {{ __('settings.loyalty_every_amount_help') }}
            </small>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.loyalty_points') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control" name="loyalty_point_rate"
                value="{{ $customer_setting->loyalty_point_rate }}">
            <small class="text-muted">
                {{ __('settings.loyalty_points_help') }}
            </small>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.loyalty_min_order_amount') }}</label>
            <div class="input-group">
                <span class="input-group-text">Rs</span>
                <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                    name="loyalty_min_order_amount" value="{{ $customer_setting->loyalty_min_order_amount }}">
            </div>
            <small class="text-muted">
                {{ __('settings.loyalty_min_order_amount_help') }}
            </small>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.loyalty_redemption_value') }}</label>
            <div class="input-group">
                <span class="input-group-text">Rs</span>
                <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                    name="loyalty_redemption_value" value="{{ $customer_setting->loyalty_redemption_value }}">
                <span class="input-group-text">{{ __('settings.loyalty_per_point') }}</span>
            </div>
            <small class="text-muted">
                {{ __('settings.loyalty_redemption_value_help') }}
            </small>
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#customerSettingForm','{{ url('admin/setting/customer') }}')">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</form>
