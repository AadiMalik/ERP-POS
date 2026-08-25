@php
    $wt = $website_theme_setting;
@endphp

<h5 class="mb-3">Website Theme</h5>
<p class="text-muted">
    Choose one of the 6 predefined storefront themes for an instant, complete look, or fine-tune its colors,
    typography and button style below. This controls the customer-facing website only (not this admin panel).
</p>

{{-- Preset gallery --}}
<div class="mb-4">
    <label class="form-label fw-semibold">Themes</label>
    <div class="row g-3" id="websiteThemePresetGallery">
        @foreach ($website_theme_presets as $key => $preset)
            <div class="col-md-4 col-sm-6">
                <div class="card website-theme-preset-card {{ ($wt->theme_preset ?? 'theme1') == $key ? 'border-primary' : '' }}"
                    data-preset="{{ $key }}" style="cursor:pointer;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <span class="rounded-circle me-1" style="width:18px;height:18px;display:inline-block;background:{{ $preset['colors']['primary'] }}"></span>
                            <span class="rounded-circle me-1" style="width:18px;height:18px;display:inline-block;background:{{ $preset['colors']['secondary'] }}"></span>
                            <span class="rounded-circle me-2" style="width:18px;height:18px;display:inline-block;background:{{ $preset['colors']['accent'] }}"></span>
                            <strong>{{ $preset['label'] }}</strong>
                        </div>
                        <div class="small text-muted mb-2">{{ $preset['description'] }}</div>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-apply-website-theme-preset" data-preset="{{ $key }}">
                            {{ ($wt->theme_preset ?? 'theme1') == $key ? 'Applied' : 'Apply' }}
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<form id="websiteThemeSettingForm" onsubmit="event.preventDefault(); saveWebsiteThemeSetting(this);">
    @csrf
    <input type="hidden" name="theme_preset" value="{{ $wt->theme_preset ?? 'theme1' }}">

    {{-- Colors --}}
    <div class="card mb-3">
        <div class="card-header"><strong>Colors</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-3">
                <label class="form-label">Primary</label>
                <input type="color" class="form-control form-control-color w-100" name="primary_color" value="{{ $wt->primary_color ?? '#1E9E5A' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Secondary</label>
                <input type="color" class="form-control form-control-color w-100" name="secondary_color" value="{{ $wt->secondary_color ?? '#0B3D2E' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Accent</label>
                <input type="color" class="form-control form-control-color w-100" name="accent_color" value="{{ $wt->accent_color ?? '#FF6B35' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Background</label>
                <input type="color" class="form-control form-control-color w-100" name="background_color" value="{{ $wt->background_color ?? '#FFFFFF' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Surface</label>
                <input type="color" class="form-control form-control-color w-100" name="surface_color" value="{{ $wt->surface_color ?? '#FFFFFF' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Text</label>
                <input type="color" class="form-control form-control-color w-100" name="text_color" value="{{ $wt->text_color ?? '#16241C' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Heading</label>
                <input type="color" class="form-control form-control-color w-100" name="heading_color" value="{{ $wt->heading_color ?? '#16241C' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Border</label>
                <input type="color" class="form-control form-control-color w-100" name="border_color" value="{{ $wt->border_color ?? '#E7ECE9' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Success</label>
                <input type="color" class="form-control form-control-color w-100" name="success_color" value="{{ $wt->success_color ?? '#1E9E5A' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Warning</label>
                <input type="color" class="form-control form-control-color w-100" name="warning_color" value="{{ $wt->warning_color ?? '#FFB020' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Error</label>
                <input type="color" class="form-control form-control-color w-100" name="error_color" value="{{ $wt->error_color ?? '#E5484D' }}">
            </div>
        </div>
    </div>

    {{-- Typography & Buttons --}}
    <div class="card mb-3">
        <div class="card-header"><strong>Typography &amp; Buttons</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">Font Pairing</label>
                <select class="form-select" name="font_pairing">
                    @foreach ($website_theme_font_pairings as $key => $pairing)
                        <option value="{{ $key }}" {{ ($wt->font_pairing ?? 'poppins_jakarta') == $key ? 'selected' : '' }}>{{ $pairing['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Base Font Size</label>
                <select class="form-select" name="font_size_base">
                    @foreach (['sm' => 'Small', 'md' => 'Medium (Default)', 'lg' => 'Large'] as $value => $label)
                        <option value="{{ $value }}" {{ ($wt->font_size_base ?? 'md') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Line Height / Typography</label>
                <select class="form-select" name="typography_style">
                    @foreach ($website_theme_typography_styles as $key => $style)
                        <option value="{{ $key }}" {{ ($wt->typography_style ?? 'comfortable') == $key ? 'selected' : '' }}>{{ $style['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Button Style</label>
                <select class="form-select" name="button_style">
                    @foreach ($website_theme_button_styles as $key => $style)
                        <option value="{{ $key }}" {{ ($wt->button_style ?? 'soft_pill') == $key ? 'selected' : '' }}>{{ $style['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Save Website Theme</button>
</form>
