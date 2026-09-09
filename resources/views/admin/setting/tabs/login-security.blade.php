<form id="loginSecuritySettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>{{ __('settings.login_security_title') }}</h4>
            <hr>
            <p class="text-muted">
                {{ __('settings.login_security_description') }}
            </p>
        </div>

        {{-- Google Login --}}
        <div class="col-md-12">
            <h5>{{ __('settings.google_login_title') }}</h5>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.google_login_enable') }}</label>
            <select class="form-select select2" name="is_google_enabled">
                <option value="1" {{ !empty($login_security_setting->is_google_enabled) ? 'selected' : '' }}>{{ __('common.yes') }}</option>
                <option value="0" {{ empty($login_security_setting->is_google_enabled) ? 'selected' : '' }}>{{ __('common.no') }}</option>
            </select>
        </div>

        <div class="col-md-6 mb-3"></div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.google_client_id') }}</label>
            <input type="text" class="form-control google-config-field" name="google_client_id"
                value="{{ $login_security_setting->google_client_id ?? '' }}">
            <small class="text-muted">{{ __('settings.google_client_id_help') }}</small>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.google_android_client_id') }}</label>
            <input type="text" class="form-control google-config-field" name="google_android_client_id"
                value="{{ $login_security_setting->google_android_client_id ?? '' }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.google_ios_client_id') }}</label>
            <input type="text" class="form-control google-config-field" name="google_ios_client_id"
                value="{{ $login_security_setting->google_ios_client_id ?? '' }}">
        </div>

        <div class="col-md-12"><hr></div>

        {{-- Facebook Login --}}
        <div class="col-md-12">
            <h5>{{ __('settings.facebook_login_title') }}</h5>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.facebook_login_enable') }}</label>
            <select class="form-select select2" name="is_facebook_enabled">
                <option value="1" {{ !empty($login_security_setting->is_facebook_enabled) ? 'selected' : '' }}>{{ __('common.yes') }}</option>
                <option value="0" {{ empty($login_security_setting->is_facebook_enabled) ? 'selected' : '' }}>{{ __('common.no') }}</option>
            </select>
        </div>

        <div class="col-md-6 mb-3"></div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.facebook_app_id') }}</label>
            <input type="text" class="form-control facebook-config-field" name="facebook_app_id"
                value="{{ $login_security_setting->facebook_app_id ?? '' }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>
                {{ __('settings.facebook_app_secret') }}
                @if (!empty($login_security_setting) && $login_security_setting->hasFacebookAppSecret())
                    <small class="text-muted">{{ __('settings.login_security_keep_existing') }}</small>
                @endif
            </label>
            <input type="password" class="form-control facebook-config-field" name="facebook_app_secret" value="">
        </div>

        <div class="col-md-12"><hr></div>

        {{-- CAPTCHA --}}
        <div class="col-md-12">
            <h5>{{ __('settings.captcha_title') }}</h5>
            <p class="text-muted">{{ __('settings.captcha_description') }}</p>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.captcha_enable') }}</label>
            <select class="form-select select2" name="is_captcha_enabled">
                <option value="1" {{ !empty($login_security_setting->is_captcha_enabled) ? 'selected' : '' }}>{{ __('common.yes') }}</option>
                <option value="0" {{ empty($login_security_setting->is_captcha_enabled) ? 'selected' : '' }}>{{ __('common.no') }}</option>
            </select>
        </div>

        <div class="col-md-6 mb-3"></div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.recaptcha_site_key') }}</label>
            <input type="text" class="form-control captcha-config-field" name="recaptcha_site_key"
                value="{{ $login_security_setting->recaptcha_site_key ?? '' }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>
                {{ __('settings.recaptcha_secret_key') }}
                @if (!empty($login_security_setting) && $login_security_setting->hasRecaptchaSecretKey())
                    <small class="text-muted">{{ __('settings.login_security_keep_existing') }}</small>
                @endif
            </label>
            <input type="password" class="form-control captcha-config-field" name="recaptcha_secret_key" value="">
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#loginSecuritySettingForm','{{ url('admin/setting/login-security') }}')">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</form>
