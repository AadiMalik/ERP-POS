<form id="fbrSettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>FBR Configuration</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <label>Enable FBR Integration</label>
            <select class="form-select select2" name="enable_fbr">
                <option value="1" {{ $fbr_setting->enable_fbr == 1 ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ $fbr_setting->enable_fbr == 0 ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>Environment</label>
            <select class="form-select select2 fbr-config-field" name="fbr_environment">
                <option value="sandbox"
                    {{ $fbr_setting->fbr_environment == 'sandbox' ? 'selected' : '' }}>
                    Sandbox
                </option>
                <option value="production"
                    {{ $fbr_setting->fbr_environment == 'production' ? 'selected' : '' }}>
                    Production
                </option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>POS ID</label>
            <input type="text"
                class="form-control fbr-config-field"
                name="fbr_pos_id"
                value="{{ $fbr_setting->fbr_pos_id }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>License Key</label>
            <input type="text"
                class="form-control fbr-config-field"
                name="fbr_license_key"
                value="{{ $fbr_setting->fbr_license_key }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>NTN</label>
            <input type="text"
                class="form-control fbr-config-field"
                name="fbr_ntn"
                value="{{ $fbr_setting->fbr_ntn }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>STRN</label>
            <input type="text"
                class="form-control fbr-config-field"
                name="fbr_strn"
                value="{{ $fbr_setting->fbr_strn }}">
        </div>

        <div class="col-md-12 mb-3">
            <label>Sandbox URL</label>
            <input type="text"
                class="form-control fbr-config-field"
                name="fbr_sandbox_url"
                value="{{ $fbr_setting->fbr_sandbox_url }}">
        </div>

        <div class="col-md-12 mb-3">
            <label>Production URL</label>
            <input type="text"
                class="form-control fbr-config-field"
                name="fbr_production_url"
                value="{{ $fbr_setting->fbr_production_url }}">
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button"
                    class="btn btn-primary"
                    onclick="saveSetting('#fbrSettingForm','{{ url('admin/setting/fbr') }}')">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>