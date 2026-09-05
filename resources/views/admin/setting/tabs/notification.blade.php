<form id="notificationSettingForm">
    @csrf
    <div class="row">
        <div class="col-md-12">
            <h4>{{ __('settings.notification_title') }}</h4>
            <hr>
        </div>

        <div class="col-md-12 mb-2">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="sound_enabled"
                    {{ $notification_setting->sound_enabled ? 'checked' : '' }}>
                <label>{{ __('settings.notification_sound_enabled') }}</label>
            </div>
        </div>

        <div class="col-md-12">
            <hr>
            <h6>{{ __('settings.notification_payment_due_alert') }}</h6>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="payment_due_alert_enabled"
                    {{ $notification_setting->payment_due_alert_enabled ? 'checked' : '' }}>
                <label>{{ __('settings.notification_enable_payment_due') }}</label>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <label>{{ __('settings.notification_days_before_due') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="payment_due_days_before" value="{{ $notification_setting->payment_due_days_before }}">
        </div>

        <div class="col-md-12">
            <hr>
            <h6>{{ __('settings.notification_credit_limit_alert') }}</h6>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="credit_limit_alert_enabled"
                    {{ $notification_setting->credit_limit_alert_enabled ? 'checked' : '' }}>
                <label>{{ __('settings.notification_enable_credit_limit') }}</label>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <label>{{ __('settings.notification_credit_limit_threshold') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="credit_limit_threshold_percent" value="{{ $notification_setting->credit_limit_threshold_percent }}">
        </div>

        <div class="col-md-12">
            <hr>
            <h6>{{ __('settings.notification_supplier_payment_reminder') }}</h6>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="supplier_payment_reminder_enabled"
                    {{ $notification_setting->supplier_payment_reminder_enabled ? 'checked' : '' }}>
                <label>{{ __('settings.notification_enable_supplier_reminder') }}</label>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <label>{{ __('settings.notification_days_before_due') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="supplier_payment_reminder_days_before" value="{{ $notification_setting->supplier_payment_reminder_days_before }}">
        </div>

        <div class="col-md-12">
            <hr>
            <h6>{{ __('settings.notification_order_status') }}</h6>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="order_status_alert_enabled"
                    {{ $notification_setting->order_status_alert_enabled ? 'checked' : '' }}>
                <label>{{ __('settings.notification_enable_order_status') }}</label>
            </div>
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary" id="btnNotificationSetting"
                    onclick="saveSetting('#notificationSettingForm','{{ url('admin/setting/notification') }}')">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</form>
