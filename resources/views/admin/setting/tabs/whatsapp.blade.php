<form id="whatsappSettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>{{ __('settings.whatsapp_title') }}</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.whatsapp_enable') }}</label>
            <select class="form-select select2" name="enable_whatsapp">
                <option value="1" {{ $whatsapp_setting->enable_whatsapp == 1 ? 'selected' : '' }}>
                    {{ __('common.yes') }}
                </option>
                <option value="0" {{ $whatsapp_setting->enable_whatsapp == 0 ? 'selected' : '' }}>
                    {{ __('common.no') }}
                </option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.whatsapp_provider') }}</label>
            <select class="form-select select2 whatsapp-config-field" name="provider">
                <option value="">{{ __('settings.whatsapp_select_provider') }}</option>
                @foreach ($whatsapp_provider as $value => $item)
                    <option value="{{ $value }}" {{ $whatsapp_setting->provider == $value ? 'selected' : '' }}>
                        {{ $item ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.whatsapp_api_key') }}</label>
            <input type="text" class="form-control whatsapp-config-field" name="api_key"
                value="{{ $whatsapp_setting->api_key }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.whatsapp_access_token') }}</label>
            <input type="text" class="form-control whatsapp-config-field" name="access_token"
                value="{{ $whatsapp_setting->access_token }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.whatsapp_instance_id') }}</label>
            <input type="text" class="form-control whatsapp-config-field" name="instance_id"
                value="{{ $whatsapp_setting->instance_id }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.whatsapp_phone_number_id') }}</label>
            <input type="text" class="form-control whatsapp-config-field" name="phone_number_id"
                value="{{ $whatsapp_setting->phone_number_id }}">
        </div>

        <div class="col-md-12 mb-3">
            <label>{{ __('settings.whatsapp_webhook_url') }}</label>
            <input type="text" class="form-control whatsapp-config-field" name="webhook_url"
                value="{{ $whatsapp_setting->webhook_url }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.whatsapp_send_invoice') }}</label>
            <select class="form-select select2 whatsapp-config-field" name="send_invoice">
                <option value="1" {{ $whatsapp_setting->send_invoice == 1 ? 'selected' : '' }}>
                    {{ __('common.yes') }}
                </option>
                <option value="0" {{ $whatsapp_setting->send_invoice == 0 ? 'selected' : '' }}>
                    {{ __('common.no') }}
                </option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.whatsapp_send_receipt') }}</label>
            <select class="form-select select2 whatsapp-config-field" name="send_receipt">
                <option value="1" {{ $whatsapp_setting->send_receipt == 1 ? 'selected' : '' }}>
                    {{ __('common.yes') }}
                </option>
                <option value="0" {{ $whatsapp_setting->send_receipt == 0 ? 'selected' : '' }}>
                    {{ __('common.no') }}
                </option>
            </select>
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#whatsappSettingForm','{{ url('admin/setting/whatsapp') }}')">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</form>
