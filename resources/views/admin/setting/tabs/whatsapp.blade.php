<form id="whatsappSettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>WhatsApp Configuration</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <label>Enable WhatsApp Notifications</label>
            <select class="form-select select2" name="enable_whatsapp">
                <option value="1" {{ $whatsapp_setting->enable_whatsapp == 1 ? 'selected' : '' }}>
                    Yes
                </option>
                <option value="0" {{ $whatsapp_setting->enable_whatsapp == 0 ? 'selected' : '' }}>
                    No
                </option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>WhatsApp Provider</label>
            <select class="form-select select2 whatsapp-config-field" name="provider">
                <option value="">--Select Provider--</option>
                <option value="meta" {{ $whatsapp_setting->provider == 'meta' ? 'selected' : '' }}>
                    Meta Cloud API
                </option>
                <option value="green_api" {{ $whatsapp_setting->provider == 'green_api' ? 'selected' : '' }}>
                    Green API
                </option>
                <option value="twilio" {{ $whatsapp_setting->provider == 'twilio' ? 'selected' : '' }}>
                    Twilio
                </option>
                <option value="custom" {{ $whatsapp_setting->provider == 'custom' ? 'selected' : '' }}>
                    Custom API
                </option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>API Key</label>
            <input type="text"
                class="form-control whatsapp-config-field"
                name="api_key"
                value="{{ $whatsapp_setting->api_key }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>Access Token</label>
            <input type="text"
                class="form-control whatsapp-config-field"
                name="access_token"
                value="{{ $whatsapp_setting->access_token }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>Instance ID</label>
            <input type="text"
                class="form-control whatsapp-config-field"
                name="instance_id"
                value="{{ $whatsapp_setting->instance_id }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>Phone Number ID</label>
            <input type="text"
                class="form-control whatsapp-config-field"
                name="phone_number_id"
                value="{{ $whatsapp_setting->phone_number_id }}">
        </div>

        <div class="col-md-12 mb-3">
            <label>Webhook URL</label>
            <input type="text"
                class="form-control whatsapp-config-field"
                name="webhook_url"
                value="{{ $whatsapp_setting->webhook_url }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>Send Invoice on WhatsApp</label>
            <select class="form-select select2 whatsapp-config-field" name="send_invoice">
                <option value="1" {{ $whatsapp_setting->send_invoice == 1 ? 'selected' : '' }}>
                    Yes
                </option>
                <option value="0" {{ $whatsapp_setting->send_invoice == 0 ? 'selected' : '' }}>
                    No
                </option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>Send Receipt on WhatsApp</label>
            <select class="form-select select2 whatsapp-config-field" name="send_receipt">
                <option value="1" {{ $whatsapp_setting->send_receipt == 1 ? 'selected' : '' }}>
                    Yes
                </option>
                <option value="0" {{ $whatsapp_setting->send_receipt == 0 ? 'selected' : '' }}>
                    No
                </option>
            </select>
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button"
                    class="btn btn-primary"
                    onclick="saveSetting('#whatsappSettingForm','{{ url('admin/setting/whatsapp') }}')">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>