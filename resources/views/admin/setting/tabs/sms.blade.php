<form id="smsSettingForm">
    @csrf

    <div class="row">

        <div class="col-12">
            <h4>{{ __('settings.sms_title') }}</h4>
            <hr>
        </div>

        {{-- Enable SMS --}}
        <div class="col-md-6 mb-3">
            <label>{{ __('settings.sms_enable') }}</label>
            <select class="form-select select2" name="enable_sms">
                <option value="1" {{ $sms_setting->enable_sms ? 'selected' : '' }}>{{ __('common.yes') }}</option>
                <option value="0" {{ !$sms_setting->enable_sms ? 'selected' : '' }}>{{ __('common.no') }}</option>
            </select>
        </div>

        {{-- Provider --}}
        <div class="col-md-6 mb-3">
            <label>{{ __('settings.sms_provider') }}</label>
            <select class="form-select select2" id="sms_provider" name="provider">
                <option value="">{{ __('settings.sms_select_provider') }}</option>
                @foreach ($sms_provider as $value => $item)
                    <option value="{{ $value }}" {{ $sms_setting->provider == $value ? 'selected' : '' }}>
                        {{ $item ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <h5 class="mt-2">{{ __('settings.sms_connection') }}</h5>
            <hr>
        </div>

        {{-- Base URL --}}
        <div class="col-md-6 mb-3 provider-field provider-infobip provider-brandsms">
            <label>{{ __('settings.sms_base_url') }}</label>
            <input type="text" class="form-control" name="base_url" value="{{ $sms_setting->base_url }}">
        </div>

        {{-- API KEY --}}
        <div class="col-md-6 mb-3 provider-field provider-infobip provider-msg91 provider-vonage">
            <label>{{ __('settings.sms_api_key') }}</label>
            <input type="text" class="form-control" name="api_key" value="{{ $sms_setting->api_key }}">
        </div>

        {{-- Username --}}
        <div class="col-md-6 mb-3 provider-field provider-brandsms">
            <label>{{ __('settings.sms_username') }}</label>
            <input type="text" class="form-control" name="username" value="{{ $sms_setting->username }}">
        </div>

        {{-- Password --}}
        <div class="col-md-6 mb-3 provider-field provider-brandsms provider-vonage">
            <label>{{ __('settings.sms_password') }}</label>
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
            <label>{{ __('settings.sms_account_sid') }}</label>
            <input type="text" class="form-control" name="account_sid" value="{{ $sms_setting->account_sid }}">
        </div>

        {{-- Auth Token --}}
        <div class="col-md-6 mb-3 provider-field provider-twilio">
            <label>{{ __('settings.sms_auth_token') }}</label>
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
            <label>{{ __('settings.sms_sender_id') }}</label>
            <input type="text" class="form-control" name="sender_id" value="{{ $sms_setting->sender_id }}">
        </div>

        {{-- Template --}}
        <div class="col-md-6 mb-3 provider-field provider-msg91">
            <label>{{ __('settings.sms_template_id') }}</label>
            <input type="text" class="form-control" name="template_id" value="{{ $sms_setting->template_id }}">
        </div>

        {{-- Entity --}}
        <div class="col-md-6 mb-3 provider-field provider-msg91">
            <label>{{ __('settings.sms_entity_id') }}</label>
            <input type="text" class="form-control" name="entity_id" value="{{ $sms_setting->entity_id }}">
        </div>

        {{-- Flow --}}
        <div class="col-md-6 mb-3 provider-field provider-msg91">
            <label>{{ __('settings.sms_flow_id') }}</label>
            <input type="text" class="form-control" name="flow_id" value="{{ $sms_setting->flow_id }}">
        </div>

        <div class="col-12">
            <h5 class="mt-2">{{ __('settings.sms_notifications') }}</h5>
            <hr>
        </div>

        <div class="col-md-4 mb-3">
            <label>{{ __('settings.sms_invoice') }}</label>
            <select class="form-select select2" name="send_invoice_sms">
                <option value="1" {{ $sms_setting->send_invoice_sms ? 'selected' : '' }}>{{ __('common.yes') }}</option>
                <option value="0" {{ !$sms_setting->send_invoice_sms ? 'selected' : '' }}>{{ __('common.no') }}</option>
            </select>
        </div>

        <div class="col-md-4 mb-3">
            <label>{{ __('settings.sms_due_reminder') }}</label>
            <select class="form-select select2" name="send_due_sms">
                <option value="1" {{ $sms_setting->send_due_sms ? 'selected' : '' }}>{{ __('common.yes') }}</option>
                <option value="0" {{ !$sms_setting->send_due_sms ? 'selected' : '' }}>{{ __('common.no') }}</option>
            </select>
        </div>

        <div class="col-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#smsSettingForm','{{ url('admin/setting/sms') }}')">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </div>

    </div>
</form>
