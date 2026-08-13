<form id="praSettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>PRA Configuration</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <label>Enable PRA Integration</label>
            <select class="form-select select2" name="enable_pra">
                <option value="1" {{ $pra_setting->enable_pra == 1 ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ $pra_setting->enable_pra == 0 ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>PRA Code Prefix</label>
            <input type="text"
                class="form-control pra-config-field"
                name="pra_code_prefix"
                value="{{ $pra_setting->pra_code_prefix }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>PRA Registration No</label>
            <input type="text"
                class="form-control pra-config-field"
                name="pra_registration_no"
                value="{{ $pra_setting->pra_registration_no }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>PRA API Key</label>
            <input type="text"
                class="form-control pra-config-field"
                name="pra_api_key"
                value="{{ $pra_setting->pra_api_key }}">
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button"
                    class="btn btn-primary"
                    onclick="saveSetting('#praSettingForm','{{ url('admin/setting/pra') }}')">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>
