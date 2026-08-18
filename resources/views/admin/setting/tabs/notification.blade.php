<form id="notificationSettingForm">
    @csrf
    <div class="row">
        <div class="col-md-12">
            <h4>Notification Setting</h4>
            <hr>
        </div>

        <div class="col-md-12 mb-2">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="sound_enabled"
                    {{ $notification_setting->sound_enabled ? 'checked' : '' }}>
                <label>Play Sound on New Notification</label>
            </div>
        </div>

        <div class="col-md-12">
            <hr>
            <h6>Payment Due Alert</h6>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="payment_due_alert_enabled"
                    {{ $notification_setting->payment_due_alert_enabled ? 'checked' : '' }}>
                <label>Enable Payment Due Alert</label>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <label>Days Before Due Date</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="payment_due_days_before" value="{{ $notification_setting->payment_due_days_before }}">
        </div>

        <div class="col-md-12">
            <hr>
            <h6>Customer Credit Limit Alert</h6>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="credit_limit_alert_enabled"
                    {{ $notification_setting->credit_limit_alert_enabled ? 'checked' : '' }}>
                <label>Enable Credit Limit Alert</label>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <label>Trigger at % of Credit Limit</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="credit_limit_threshold_percent" value="{{ $notification_setting->credit_limit_threshold_percent }}">
        </div>

        <div class="col-md-12">
            <hr>
            <h6>Supplier Payment Reminder</h6>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="supplier_payment_reminder_enabled"
                    {{ $notification_setting->supplier_payment_reminder_enabled ? 'checked' : '' }}>
                <label>Enable Supplier Payment Reminder</label>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <label>Days Before Due Date</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                name="supplier_payment_reminder_days_before" value="{{ $notification_setting->supplier_payment_reminder_days_before }}">
        </div>

        <div class="col-md-12">
            <hr>
            <h6>Order Status Notification</h6>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="order_status_alert_enabled"
                    {{ $notification_setting->order_status_alert_enabled ? 'checked' : '' }}>
                <label>Enable Order Status Alert</label>
            </div>
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary" id="btnNotificationSetting"
                    onclick="saveSetting('#notificationSettingForm','{{ url('admin/setting/notification') }}')">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>
