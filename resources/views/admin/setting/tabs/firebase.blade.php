<form id="firebaseSettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>{{ __('settings.firebase_title') }}</h4>
            <hr>
            <p class="text-muted">
                {{ __('settings.firebase_description') }}
            </p>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.firebase_active') }}</label>
            <select class="form-select select2" name="is_active">
                <option value="1" {{ !empty($firebase_setting->is_active) ? 'selected' : '' }}>{{ __('common.yes') }}</option>
                <option value="0" {{ empty($firebase_setting->is_active) ? 'selected' : '' }}>{{ __('common.no') }}</option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.firebase_project_id') }}</label>
            <input type="text" class="form-control" name="project_id"
                value="{{ $firebase_setting->project_id ?? '' }}">
        </div>

        <div class="col-md-12 mb-3">
            <label>{{ __('settings.firebase_client_email') }}</label>
            <input type="email" class="form-control" name="client_email"
                value="{{ $firebase_setting->client_email ?? '' }}">
        </div>

        <div class="col-md-12 mb-3">
            <label>
                {{ __('settings.firebase_private_key') }}
                @if (!empty($firebase_setting) && $firebase_setting->hasPrivateKey())
                    <small class="text-muted">{{ __('settings.firebase_keep_existing') }}</small>
                @endif
            </label>
            <textarea class="form-control font-monospace" name="private_key" rows="6"
                placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----"></textarea>
            <small class="text-muted">{{ __('settings.firebase_private_key_help') }}</small>
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#firebaseSettingForm','{{ url('admin/setting/firebase') }}')">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</form>
