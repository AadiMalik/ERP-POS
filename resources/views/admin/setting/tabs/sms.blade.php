<form id="smsSettingForm">
    @csrf

    <div class="row">

        <div class="col-12">
            <h4>SMS Configuration</h4>
            <hr>
        </div>

        {{-- Enable SMS --}}
        <div class="col-md-6 mb-3">
            <label>Enable SMS</label>
            <select class="form-select select2" name="enable_sms">
                <option value="1" {{ $sms_setting->enable_sms ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ !$sms_setting->enable_sms ? 'selected' : '' }}>No</option>
            </select>
        </div>

        {{-- Provider --}}
        <div class="col-md-6 mb-3">
            <label>Provider</label>
            <select class="form-select select2" id="sms_provider" name="provider">
                <option value="">--Select Provider--</option>
                @foreach ($sms_provider as $value => $item)
                    <option value="{{ $value }}" {{ $sms_setting->provider == $value ? 'selected' : '' }}>
                        {{ $item ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <h5 class="mt-2">Connection</h5>
            <hr>
        </div>

        {{-- Base URL --}}
        <div class="col-md-6 mb-3 provider-field provider-infobip provider-brandsms">
            <label>Base URL</label>
            <input type="text" class="form-control" name="base_url" value="{{ $sms_setting->base_url }}">
        </div>

        {{-- API KEY --}}
        <div class="col-md-6 mb-3 provider-field provider-infobip provider-msg91 provider-vonage">
            <label>API Key</label>
            <input type="text" class="form-control" name="api_key" value="{{ $sms_setting->api_key }}">
        </div>

        {{-- Username --}}
        <div class="col-md-6 mb-3 provider-field provider-brandsms">
            <label>Username</label>
            <input type="text" class="form-control" name="username" value="{{ $sms_setting->username }}">
        </div>

        {{-- Password --}}
        <div class="col-md-6 mb-3 provider-field provider-brandsms provider-vonage">
            <label>Password / API Secret</label>
            @include('partials.password-input', [
                'name' => 'password',
                'id' => 'sms_password',
                'autocomplete' => 'new-password',
                'required' => false,
                'value' => $sms_setting->password,
            ])
        </div>

        {{-- Account SID --}}
        <div class="col-md-6 mb-3 provider-field provider-twilio">
            <label>Account SID</label>
            <input type="text" class="form-control" name="account_sid" value="{{ $sms_setting->account_sid }}">
        </div>

        {{-- Auth Token --}}
        <div class="col-md-6 mb-3 provider-field provider-twilio">
            <label>Auth Token</label>
            @include('partials.password-input', [
                'name' => 'auth_token',
                'id' => 'sms_auth_token',
                'autocomplete' => 'new-password',
                'required' => false,
                'value' => $sms_setting->auth_token,
            ])
        </div>

        {{-- Sender --}}
        <div
            class="col-md-6 mb-3 provider-field provider-twilio provider-infobip provider-brandsms provider-msg91 provider-vonage">
            <label>Sender ID</label>
            <input type="text" class="form-control" name="sender_id" value="{{ $sms_setting->sender_id }}">
        </div>

        {{-- Template --}}
        <div class="col-md-6 mb-3 provider-field provider-msg91">
            <label>Template ID</label>
            <input type="text" class="form-control" name="template_id" value="{{ $sms_setting->template_id }}">
        </div>

        {{-- Entity --}}
        <div class="col-md-6 mb-3 provider-field provider-msg91">
            <label>Entity ID</label>
            <input type="text" class="form-control" name="entity_id" value="{{ $sms_setting->entity_id }}">
        </div>

        {{-- Flow --}}
        <div class="col-md-6 mb-3 provider-field provider-msg91">
            <label>Flow ID</label>
            <input type="text" class="form-control" name="flow_id" value="{{ $sms_setting->flow_id }}">
        </div>

        <div class="col-12">
            <h5 class="mt-2">Notifications</h5>
            <hr>
        </div>

        <div class="col-md-4 mb-3">
            <label>Invoice SMS</label>
            <select class="form-select select2" name="send_invoice_sms">
                <option value="1" {{ $sms_setting->send_invoice_sms ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ !$sms_setting->send_invoice_sms ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-md-4 mb-3">
            <label>Due Reminder SMS</label>
            <select class="form-select select2" name="send_due_sms">
                <option value="1" {{ $sms_setting->send_due_sms ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ !$sms_setting->send_due_sms ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#smsSettingForm','{{ url('admin/setting/sms') }}')">
                    Save Changes
                </button>
            </div>
        </div>

    </div>
</form>
