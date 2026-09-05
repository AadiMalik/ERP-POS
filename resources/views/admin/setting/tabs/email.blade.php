<form id="emailSettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>{{ __('settings.email_title') }}</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.email_enable_notifications') }}</label>
            <select class="form-select select2" name="enable_email_notifications">
                <option value="1" {{ $email_setting->enable_email_notifications == 1 ? 'selected' : '' }}>
                    {{ __('common.yes') }}
                </option>
                <option value="0" {{ $email_setting->enable_email_notifications == 0 ? 'selected' : '' }}>
                    {{ __('common.no') }}
                </option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.email_mail_mailer') }}<span class="text-danger">*</span></label>
            <select class="form-select select2 email-config-field" name="mail_mailer">
                <option value="">{{ __('settings.email_select_mailer') }}</option>
                @foreach ($email_mailer as $value => $item)
                    <option value="{{ $value }}" {{ $email_setting->mail_mailer == $value ? 'selected' : '' }}>
                        {{ $item ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.email_smtp_host') }}</label>
            <input type="text" class="form-control email-config-field" name="mail_host"
                value="{{ $email_setting->mail_host }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.email_smtp_port') }}</label>
            <input type="text" class="form-control email-config-field" name="mail_port"
                onkeypress="return isNumberKey(event)" value="{{ $email_setting->mail_port }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.email_username') }}</label>
            <input type="text" class="form-control email-config-field" name="mail_username"
                value="{{ $email_setting->mail_username }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.email_password') }}</label>
            @include('partials.password-input', [
                'name' => 'mail_password',
                'id' => 'mail_password',
                'class' => 'form-control email-config-field',
                'autocomplete' => 'new-password',
                'required' => false,
                'value' => $email_setting->mail_password,
            ])
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.email_encryption') }}</label>
            <select class="form-select select2 email-config-field" name="mail_encryption">
                <option value="">{{ __('settings.email_encryption_none') }}</option>
                <option value="tls" {{ $email_setting->mail_encryption == 'tls' ? 'selected' : '' }}>TLS</option>
                <option value="ssl" {{ $email_setting->mail_encryption == 'ssl' ? 'selected' : '' }}>SSL</option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.email_from_address') }}</label>
            <input type="email" class="form-control email-config-field" name="mail_from_address"
                value="{{ $email_setting->mail_from_address }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.email_from_name') }}</label>
            <input type="text" class="form-control email-config-field" name="mail_from_name"
                value="{{ $email_setting->mail_from_name }}">
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#emailSettingForm','{{ url('admin/setting/email') }}')">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</form>
