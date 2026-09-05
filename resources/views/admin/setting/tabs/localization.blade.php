<h5 class="mb-3">{{ __('settings.localization_title') }}</h5>
<p class="text-muted">
    {{ __('settings.localization_description') }}
</p>

<form id="localizationSettingForm" onsubmit="event.preventDefault(); saveLocalizationSetting(this);">
    @csrf
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">{{ __('settings.display_language') }}</label>
            <select class="form-select select2" name="display_language">
                @foreach ($languages as $code => $lang)
                    <option value="{{ $code }}" {{ ($localization_setting->display_language ?? 'en') == $code ? 'selected' : '' }}>
                        {{ $lang['name'] }} ({{ $lang['native_name'] }})
                    </option>
                @endforeach
            </select>
            <small class="text-muted">{{ __('settings.display_language_help') }}</small>
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('settings.input_language') }}</label>
            <select class="form-select select2" name="input_language">
                @foreach ($languages as $code => $lang)
                    <option value="{{ $code }}" {{ ($localization_setting->input_language ?? 'en') == $code ? 'selected' : '' }}>
                        {{ $lang['name'] }} ({{ $lang['native_name'] }})
                    </option>
                @endforeach
            </select>
            <small class="text-muted">{{ __('settings.input_language_help') }}</small>
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('settings.direction') }}</label>
            <select class="form-select" name="direction_override">
                @foreach ([
                    'auto' => __('settings.direction_auto'),
                    'ltr' => __('settings.direction_ltr'),
                    'rtl' => __('settings.direction_rtl'),
                ] as $value => $label)
                    <option value="{{ $value }}" {{ ($localization_setting->direction_override ?? 'auto') == $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">{{ __('settings.direction_help') }}</small>
        </div>
    </div>

    <button type="submit" class="btn btn-primary mt-4">{{ __('settings.save_localization') }}</button>
</form>
