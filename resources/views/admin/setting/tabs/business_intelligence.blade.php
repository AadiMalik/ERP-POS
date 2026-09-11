<form id="businessIntelligenceSettingForm">
    @csrf
    <div class="row">
        <div class="col-md-12">
            <h4>{{ __('settings.bi_title') }}</h4>
            <p class="text-muted">{{ __('settings.bi_help') }}</p>
            <hr>
        </div>

        <div class="col-md-4 mb-3">
            <label>{{ __('settings.bi_discount_change_threshold') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="discount_change_threshold_percent" value="{{ $business_intelligence_setting->discount_change_threshold_percent }}">
        </div>
        <div class="col-md-4 mb-3">
            <label>{{ __('settings.bi_voucher_change_threshold') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="voucher_change_threshold_percent" value="{{ $business_intelligence_setting->voucher_change_threshold_percent }}">
        </div>
        <div class="col-md-4 mb-3">
            <label>{{ __('settings.bi_complimentary_sales_percent') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="complimentary_sales_percent_threshold" value="{{ $business_intelligence_setting->complimentary_sales_percent_threshold }}">
        </div>
        <div class="col-md-4 mb-3">
            <label>{{ __('settings.bi_high_discount_percent') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="high_discount_percent_threshold" value="{{ $business_intelligence_setting->high_discount_percent_threshold }}">
        </div>
        <div class="col-md-4 mb-3">
            <label>{{ __('settings.bi_high_return_rate') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="high_return_rate_percent" value="{{ $business_intelligence_setting->high_return_rate_percent }}">
        </div>
        <div class="col-md-4 mb-3">
            <label>{{ __('settings.bi_high_cancellation_rate') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="high_cancellation_rate_percent" value="{{ $business_intelligence_setting->high_cancellation_rate_percent }}">
        </div>
        <div class="col-md-4 mb-3">
            <label>{{ __('settings.bi_dead_stock_days') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="dead_stock_days" value="{{ $business_intelligence_setting->dead_stock_days }}">
        </div>
        <div class="col-md-4 mb-3">
            <label>{{ __('settings.bi_slow_moving_days') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="slow_moving_days" value="{{ $business_intelligence_setting->slow_moving_days }}">
        </div>
        <div class="col-md-4 mb-3">
            <label>{{ __('settings.bi_delayed_order_hours') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="delayed_order_hours" value="{{ $business_intelligence_setting->delayed_order_hours }}">
        </div>
        <div class="col-md-4 mb-3">
            <label>{{ __('settings.bi_offline_sync_hours') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="offline_sync_stale_hours" value="{{ $business_intelligence_setting->offline_sync_stale_hours }}">
        </div>
        <div class="col-md-4 mb-3">
            <label>{{ __('settings.bi_high_waste_percent') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="high_waste_percent_of_stock" value="{{ $business_intelligence_setting->high_waste_percent_of_stock }}">
        </div>
        <div class="col-md-4 mb-3">
            <label>{{ __('settings.bi_repeat_late_count') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="attendance_repeat_late_count" value="{{ $business_intelligence_setting->attendance_repeat_late_count }}">
        </div>
        <div class="col-md-4 mb-3">
            <label>{{ __('settings.bi_low_margin_percent') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="low_margin_percent" value="{{ $business_intelligence_setting->low_margin_percent }}">
        </div>
        <div class="col-md-4 mb-3">
            <label>{{ __('settings.bi_excellent_sales_growth') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="excellent_sales_growth_percent" value="{{ $business_intelligence_setting->excellent_sales_growth_percent }}">
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#businessIntelligenceSettingForm','{{ url('admin/setting/business-intelligence') }}')">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</form>
