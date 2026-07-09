<form id="smsSettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>SMS Configuration</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <label>Enable SMS Notifications</label>
            <select class="form-select select2" name="enable_sms">
                <option value="1" {{ $sms_setting->enable_sms == 1 ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ $sms_setting->enable_sms == 0 ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>SMS Provider</label>
            <select class="form-select select2 sms-config-field" name="provider">
                <option value="">--Select Provider--</option>
                <option value="twilio" {{ $sms_setting->provider == 'twilio' ? 'selected' : '' }}>Twilio</option>
                <option value="vonage" {{ $sms_setting->provider == 'vonage' ? 'selected' : '' }}>Vonage (Nexmo)</option>
                <option value="msg91" {{ $sms_setting->provider == 'msg91' ? 'selected' : '' }}>MSG91</option>
                <option value="custom" {{ $sms_setting->provider == 'custom' ? 'selected' : '' }}>Custom API</option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>API Key</label>
            <input type="text"
                class="form-control sms-config-field"
                name="api_key"
                value="{{ $sms_setting->api_key }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>Sender ID</label>
            <input type="text"
                class="form-control sms-config-field"
                name="sender_id"
                value="{{ $sms_setting->sender_id }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>Username</label>
            <input type="text"
                class="form-control sms-config-field"
                name="username"
                value="{{ $sms_setting->username }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>Password</label>
            <input type="password"
                class="form-control sms-config-field"
                name="password"
                value="{{ $sms_setting->password }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>Send Invoice SMS</label>
            <select class="form-select select2 sms-config-field" name="send_invoice_sms">
                <option value="1" {{ $sms_setting->send_invoice_sms == 1 ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ $sms_setting->send_invoice_sms == 0 ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>Send Due Reminder SMS</label>
            <select class="form-select select2 sms-config-field" name="send_due_sms">
                <option value="1" {{ $sms_setting->send_due_sms == 1 ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ $sms_setting->send_due_sms == 0 ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button"
                    class="btn btn-primary"
                    onclick="saveSetting('#smsSettingForm','{{ url('admin/setting/sms') }}')">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>

