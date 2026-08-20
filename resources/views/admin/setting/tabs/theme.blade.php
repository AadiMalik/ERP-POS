@php
    $sidebar_config = $theme_setting->sidebar_config ?? [];
    $header_config = $theme_setting->header_config ?? [];
    $footer_config = $theme_setting->footer_config ?? [];
    $content_config = $theme_setting->content_config ?? [];
@endphp

<h5 class="mb-3">Theme / Appearance</h5>
<p class="text-muted">
    Choose a preset for an instant, professionally designed look, or fine-tune every part of the Sidebar, Header,
    Footer and Content area below. Changes apply across the entire ERP for all users of this business.
</p>

{{-- Preset gallery --}}
<div class="mb-4">
    <label class="form-label fw-semibold">Presets</label>
    <div class="row g-3" id="themePresetGallery">
        @foreach ($theme_presets as $key => $preset)
            <div class="col-md-4 col-sm-6">
                <div class="card theme-preset-card {{ ($theme_setting->preset ?? 'sneat_default') == $key ? 'border-primary' : '' }}"
                    data-preset="{{ $key }}" style="cursor:pointer;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <span class="rounded-circle me-1" style="width:18px;height:18px;display:inline-block;background:{{ $preset['primary_color'] }}"></span>
                            <span class="rounded-circle me-1" style="width:18px;height:18px;display:inline-block;background:{{ $preset['secondary_color'] }}"></span>
                            <span class="rounded-circle me-2" style="width:18px;height:18px;display:inline-block;background:{{ $preset['accent_color'] }}"></span>
                            <strong>{{ $preset['label'] }}</strong>
                        </div>
                        <div class="small text-muted mb-2">
                            Sidebar: {{ ucfirst($preset['sidebar_config']['skin']) }} &middot;
                            Header: {{ ucfirst($preset['header_config']['style']) }}
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-apply-preset" data-preset="{{ $key }}">
                            {{ ($theme_setting->preset ?? 'sneat_default') == $key ? 'Applied' : 'Apply' }}
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<form id="themeSettingForm" onsubmit="event.preventDefault(); saveThemeSetting(this);">
    @csrf
    <input type="hidden" name="preset" value="{{ $theme_setting->preset ?? 'sneat_default' }}">

    {{-- Colors & Typography --}}
    <div class="card mb-3">
        <div class="card-header"><strong>Colors &amp; Typography</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">Primary Color</label>
                <input type="color" class="form-control form-control-color w-100" name="primary_color"
                    value="{{ $theme_setting->primary_color ?? '#696cff' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Secondary Color</label>
                <input type="color" class="form-control form-control-color w-100" name="secondary_color"
                    value="{{ $theme_setting->secondary_color ?? '#8592a3' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Accent Color</label>
                <input type="color" class="form-control form-control-color w-100" name="accent_color"
                    value="{{ $theme_setting->accent_color ?? '#03c3ec' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Font Family</label>
                <select class="form-select" name="font_family">
                    @foreach ([
                        "'Public Sans', sans-serif" => 'Public Sans (Default)',
                        "'Inter', sans-serif" => 'Inter',
                        "'Roboto', sans-serif" => 'Roboto',
                        "'Poppins', sans-serif" => 'Poppins',
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($theme_setting->font_family ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Base Font Size</label>
                <select class="form-select" name="font_size_base">
                    @foreach (['sm' => 'Small', 'md' => 'Medium (Default)', 'lg' => 'Large'] as $value => $label)
                        <option value="{{ $value }}" {{ ($theme_setting->font_size_base ?? 'md') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="card mb-3">
        <div class="card-header"><strong>Sidebar</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-3">
                <label class="form-label">Skin</label>
                <select class="form-select" name="sidebar_config[skin]">
                    @foreach (['light' => 'Light', 'dark' => 'Dark', 'gradient' => 'Gradient'] as $value => $label)
                        <option value="{{ $value }}" {{ ($sidebar_config['skin'] ?? 'light') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Width</label>
                <select class="form-select" name="sidebar_config[width]">
                    @foreach (['compact' => 'Compact', 'default' => 'Default', 'wide' => 'Wide'] as $value => $label)
                        <option value="{{ $value }}" {{ ($sidebar_config['width'] ?? 'default') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Collapsed Behavior</label>
                <select class="form-select" name="sidebar_config[collapsed_behavior]">
                    @foreach (['expanded' => 'Expanded by default', 'collapsed' => 'Collapsed by default', 'hover' => 'Collapsed, expand on hover'] as $value => $label)
                        <option value="{{ $value }}" {{ ($sidebar_config['collapsed_behavior'] ?? 'expanded') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Position</label>
                <select class="form-select" name="sidebar_config[position]">
                    @foreach (['static' => 'Scrolls with page', 'fixed' => 'Fixed / sticky', 'offcanvas' => 'Off-canvas (overlay)'] as $value => $label)
                        <option value="{{ $value }}" {{ ($sidebar_config['position'] ?? 'static') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-header"><strong>Header</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">Style</label>
                <select class="form-select" name="header_config[style]">
                    @foreach (['light' => 'Light', 'dark' => 'Dark', 'colored' => 'Colored (Primary)'] as $value => $label)
                        <option value="{{ $value }}" {{ ($header_config['style'] ?? 'light') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Position</label>
                <select class="form-select" name="header_config[position]">
                    @foreach (['static' => 'Normal (scrolls)', 'sticky' => 'Sticky / Fixed'] as $value => $label)
                        <option value="{{ $value }}" {{ ($header_config['position'] ?? 'static') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Layout Type</label>
                <select class="form-select" name="header_config[type]">
                    @foreach (['detached' => 'Detached (card style)', 'full' => 'Full width'] as $value => $label)
                        <option value="{{ $value }}" {{ ($header_config['type'] ?? 'detached') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="card mb-3">
        <div class="card-header"><strong>Footer</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label d-block">Visible</label>
                <input type="hidden" name="footer_config[visible]" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="footer_config[visible]" value="1"
                        {{ ($footer_config['visible'] ?? true) ? 'checked' : '' }}>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label d-block">Sticky</label>
                <input type="hidden" name="footer_config[sticky]" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="footer_config[sticky]" value="1"
                        {{ ($footer_config['sticky'] ?? false) ? 'checked' : '' }}>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Style</label>
                <select class="form-select" name="footer_config[style]">
                    @foreach (['light' => 'Light', 'dark' => 'Dark', 'colored' => 'Colored (Primary)'] as $value => $label)
                        <option value="{{ $value }}" {{ ($footer_config['style'] ?? 'light') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Content & Components --}}
    <div class="card mb-3">
        <div class="card-header"><strong>Content &amp; Components</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-3">
                <label class="form-label">Background</label>
                <select class="form-select" name="content_config[background]">
                    @foreach (['default' => 'Default', 'light' => 'Light Gray', 'dark' => 'Dark'] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['background'] ?? 'default') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Spacing</label>
                <select class="form-select" name="content_config[spacing]">
                    @foreach (['comfortable' => 'Comfortable', 'compact' => 'Compact'] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['spacing'] ?? 'comfortable') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Card Style</label>
                <select class="form-select" name="content_config[card_style]">
                    @foreach (['flat' => 'Flat', 'shadow' => 'Shadow', 'bordered' => 'Bordered', 'gradient' => 'Gradient (colorful)'] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['card_style'] ?? 'shadow') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Border Radius</label>
                <select class="form-select" name="content_config[border_radius]">
                    @foreach (['none' => 'None', 'sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['border_radius'] ?? 'md') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Shadow Level</label>
                <select class="form-select" name="content_config[shadow_level]">
                    @foreach (['none' => 'None', 'sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['shadow_level'] ?? 'sm') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Table Style</label>
                <select class="form-select" name="content_config[table_style]">
                    @foreach (['default' => 'Default', 'striped' => 'Striped', 'bordered' => 'Bordered', 'borderless' => 'Borderless', 'compact' => 'Compact'] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['table_style'] ?? 'default') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Button Style</label>
                <select class="form-select" name="content_config[button_style]">
                    @foreach (['default' => 'Default', 'rounded' => 'Rounded', 'pill' => 'Pill', 'square' => 'Square'] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['button_style'] ?? 'default') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Form Control Style</label>
                <select class="form-select" name="content_config[form_style]">
                    @foreach (['default' => 'Default', 'rounded' => 'Rounded', 'flat' => 'Flat'] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['form_style'] ?? 'default') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Animation Level</label>
                <select class="form-select" name="content_config[animation_level]">
                    @foreach (['none' => 'None', 'subtle' => 'Subtle (Default)', 'rich' => 'Rich'] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['animation_level'] ?? 'subtle') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Hover lift, transitions and chart/card entrance motion.</small>
            </div>
        </div>
    </div>

    {{-- Filter Sections --}}
    <div class="card mb-3">
        <div class="card-header"><strong>Filter Sections</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">Style</label>
                <select class="form-select" name="content_config[filter_style]">
                    @foreach (['compact' => 'Compact', 'card' => 'Card', 'bordered' => 'Bordered', 'inline' => 'Inline', 'collapsible' => 'Collapsible'] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['filter_style'] ?? 'compact') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Applies to every listing page's filter panel across the ERP.</small>
            </div>
        </div>
    </div>

    {{-- Content Display --}}
    <div class="card mb-3">
        <div class="card-header"><strong>Content Display</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">Style</label>
                <select class="form-select" name="content_config[content_display_style]">
                    @foreach (['card' => 'Clean Card', 'table' => 'Table Focused', 'grid' => 'Grid', 'dashboard' => 'Modern Dashboard'] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['content_display_style'] ?? 'card') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Save Theme Settings</button>
</form>
