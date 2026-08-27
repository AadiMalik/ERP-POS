<form id="firebaseSettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>Firebase / FCM Configuration</h4>
            <hr>
            <p class="text-muted">
                Business-wise Firebase service account for push notifications.
                Required before starting broadcast campaigns.
            </p>
        </div>

        <div class="col-md-6 mb-3">
            <label>Active</label>
            <select class="form-select select2" name="is_active">
                <option value="1" {{ !empty($firebase_setting->is_active) ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ empty($firebase_setting->is_active) ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>Project ID</label>
            <input type="text" class="form-control" name="project_id"
                value="{{ $firebase_setting->project_id ?? '' }}">
        </div>

        <div class="col-md-12 mb-3">
            <label>Client Email</label>
            <input type="email" class="form-control" name="client_email"
                value="{{ $firebase_setting->client_email ?? '' }}">
        </div>

        <div class="col-md-12 mb-3">
            <label>
                Private Key
                @if (!empty($firebase_setting) && $firebase_setting->hasPrivateKey())
                    <small class="text-muted">(leave blank to keep existing)</small>
                @endif
            </label>
            <textarea class="form-control font-monospace" name="private_key" rows="6"
                placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----"></textarea>
            <small class="text-muted">Paste the service account private_key from the Firebase JSON. Stored encrypted.</small>
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#firebaseSettingForm','{{ url('admin/setting/firebase') }}')">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>
