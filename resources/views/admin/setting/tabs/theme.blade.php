@php
    $sidebar_config = $theme_setting->sidebar_config ?? [];
    $header_config = $theme_setting->header_config ?? [];
    $footer_config = $theme_setting->footer_config ?? [];
    $content_config = $theme_setting->content_config ?? [];
@endphp

<h5 class="mb-3">{{ __('settings.theme_title') }}</h5>
<p class="text-muted">
    {{ __('settings.theme_description') }}
</p>

{{-- Preset gallery --}}
<div class="mb-4">
    <label class="form-label fw-semibold">{{ __('settings.theme_presets') }}</label>
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
                            {{ __('settings.theme_preset_sidebar', ['skin' => ucfirst($preset['sidebar_config']['skin'])]) }} &middot;
                            {{ __('settings.theme_preset_header', ['style' => ucfirst($preset['header_config']['style'])]) }}
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-apply-preset" data-preset="{{ $key }}">
                            {{ ($theme_setting->preset ?? 'sneat_default') == $key ? __('settings.theme_applied') : __('settings.theme_apply') }}
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
        <div class="card-header"><strong>{{ __('settings.theme_colors_typography') }}</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">{{ __('settings.theme_primary_color') }}</label>
                <input type="color" class="form-control form-control-color w-100" name="primary_color"
                    value="{{ $theme_setting->primary_color ?? '#3833C8' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('settings.theme_secondary_color') }}</label>
                <input type="color" class="form-control form-control-color w-100" name="secondary_color"
                    value="{{ $theme_setting->secondary_color ?? '#8592a3' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('settings.theme_accent_color') }}</label>
                <input type="color" class="form-control form-control-color w-100" name="accent_color"
                    value="{{ $theme_setting->accent_color ?? '#03c3ec' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('settings.theme_font_family') }}</label>
                <select class="form-select" name="font_family">
                    @foreach ([
                        "'Public Sans', sans-serif" => __('settings.theme_font_public_sans'),
                        "'Inter', sans-serif" => __('settings.theme_font_inter'),
                        "'Roboto', sans-serif" => __('settings.theme_font_roboto'),
                        "'Poppins', sans-serif" => __('settings.theme_font_poppins'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($theme_setting->font_family ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('settings.theme_base_font_size') }}</label>
                <select class="form-select" name="font_size_base">
                    @foreach ([
                        'sm' => __('settings.theme_size_sm'),
                        'md' => __('settings.theme_size_md'),
                        'lg' => __('settings.theme_size_lg'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($theme_setting->font_size_base ?? 'md') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="card mb-3">
        <div class="card-header"><strong>{{ __('settings.theme_sidebar') }}</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-3">
                <label class="form-label">{{ __('settings.theme_skin') }}</label>
                <select class="form-select" name="sidebar_config[skin]">
                    @foreach ([
                        'light' => __('settings.theme_skin_light'),
                        'dark' => __('settings.theme_skin_dark'),
                        'gradient' => __('settings.theme_skin_gradient'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($sidebar_config['skin'] ?? 'light') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('settings.theme_width') }}</label>
                <select class="form-select" name="sidebar_config[width]">
                    @foreach ([
                        'compact' => __('settings.theme_width_compact'),
                        'default' => __('settings.theme_width_default'),
                        'wide' => __('settings.theme_width_wide'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($sidebar_config['width'] ?? 'default') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('settings.theme_collapsed_behavior') }}</label>
                <select class="form-select" name="sidebar_config[collapsed_behavior]">
                    @foreach ([
                        'expanded' => __('settings.theme_collapsed_expanded'),
                        'collapsed' => __('settings.theme_collapsed_collapsed'),
                        'hover' => __('settings.theme_collapsed_hover'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($sidebar_config['collapsed_behavior'] ?? 'expanded') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('settings.theme_position') }}</label>
                <select class="form-select" name="sidebar_config[position]">
                    @foreach ([
                        'static' => __('settings.theme_position_static'),
                        'fixed' => __('settings.theme_position_fixed'),
                        'offcanvas' => __('settings.theme_position_offcanvas'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($sidebar_config['position'] ?? 'static') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-header"><strong>{{ __('settings.theme_header') }}</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">{{ __('settings.theme_header_style') }}</label>
                <select class="form-select" name="header_config[style]">
                    @foreach ([
                        'light' => __('settings.theme_header_style_light'),
                        'dark' => __('settings.theme_header_style_dark'),
                        'colored' => __('settings.theme_header_style_colored'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($header_config['style'] ?? 'light') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('settings.theme_header_position') }}</label>
                <select class="form-select" name="header_config[position]">
                    @foreach ([
                        'static' => __('settings.theme_header_position_static'),
                        'sticky' => __('settings.theme_header_position_sticky'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($header_config['position'] ?? 'static') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('settings.theme_header_layout_type') }}</label>
                <select class="form-select" name="header_config[type]">
                    @foreach ([
                        'detached' => __('settings.theme_header_type_detached'),
                        'full' => __('settings.theme_header_type_full'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($header_config['type'] ?? 'detached') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="card mb-3">
        <div class="card-header"><strong>{{ __('settings.theme_footer') }}</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label d-block">{{ __('settings.theme_footer_visible') }}</label>
                <input type="hidden" name="footer_config[visible]" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="footer_config[visible]" value="1"
                        {{ ($footer_config['visible'] ?? true) ? 'checked' : '' }}>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label d-block">{{ __('settings.theme_footer_sticky') }}</label>
                <input type="hidden" name="footer_config[sticky]" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="footer_config[sticky]" value="1"
                        {{ ($footer_config['sticky'] ?? false) ? 'checked' : '' }}>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('settings.theme_footer_style') }}</label>
                <select class="form-select" name="footer_config[style]">
                    @foreach ([
                        'light' => __('settings.theme_header_style_light'),
                        'dark' => __('settings.theme_header_style_dark'),
                        'colored' => __('settings.theme_header_style_colored'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($footer_config['style'] ?? 'light') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Content & Components --}}
    <div class="card mb-3">
        <div class="card-header"><strong>{{ __('settings.theme_content_components') }}</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-3">
                <label class="form-label">{{ __('settings.theme_background') }}</label>
                <select class="form-select" name="content_config[background]">
                    @foreach ([
                        'default' => __('settings.theme_bg_default'),
                        'light' => __('settings.theme_bg_light'),
                        'dark' => __('settings.theme_bg_dark'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['background'] ?? 'default') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('settings.theme_spacing') }}</label>
                <select class="form-select" name="content_config[spacing]">
                    @foreach ([
                        'comfortable' => __('settings.theme_spacing_comfortable'),
                        'compact' => __('settings.theme_spacing_compact'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['spacing'] ?? 'comfortable') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('settings.theme_card_style') }}</label>
                <select class="form-select" name="content_config[card_style]">
                    @foreach ([
                        'flat' => __('settings.theme_card_flat'),
                        'shadow' => __('settings.theme_card_shadow'),
                        'bordered' => __('settings.theme_card_bordered'),
                        'gradient' => __('settings.theme_card_gradient'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['card_style'] ?? 'shadow') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('settings.theme_border_radius') }}</label>
                <select class="form-select" name="content_config[border_radius]">
                    @foreach ([
                        'none' => __('settings.theme_radius_none'),
                        'sm' => __('settings.theme_radius_sm'),
                        'md' => __('settings.theme_radius_md'),
                        'lg' => __('settings.theme_radius_lg'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['border_radius'] ?? 'md') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('settings.theme_shadow_level') }}</label>
                <select class="form-select" name="content_config[shadow_level]">
                    @foreach ([
                        'none' => __('settings.theme_shadow_none'),
                        'sm' => __('settings.theme_shadow_sm'),
                        'md' => __('settings.theme_shadow_md'),
                        'lg' => __('settings.theme_shadow_lg'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['shadow_level'] ?? 'sm') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('settings.theme_table_style') }}</label>
                <select class="form-select" name="content_config[table_style]">
                    @foreach ([
                        'default' => __('settings.theme_table_default'),
                        'striped' => __('settings.theme_table_striped'),
                        'bordered' => __('settings.theme_table_bordered'),
                        'borderless' => __('settings.theme_table_borderless'),
                        'compact' => __('settings.theme_table_compact'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['table_style'] ?? 'default') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('settings.theme_button_style') }}</label>
                <select class="form-select" name="content_config[button_style]">
                    @foreach ([
                        'default' => __('settings.theme_button_default'),
                        'rounded' => __('settings.theme_button_rounded'),
                        'pill' => __('settings.theme_button_pill'),
                        'square' => __('settings.theme_button_square'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['button_style'] ?? 'default') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('settings.theme_form_style') }}</label>
                <select class="form-select" name="content_config[form_style]">
                    @foreach ([
                        'default' => __('settings.theme_form_default'),
                        'rounded' => __('settings.theme_form_rounded'),
                        'flat' => __('settings.theme_form_flat'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['form_style'] ?? 'default') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('settings.theme_animation_level') }}</label>
                <select class="form-select" name="content_config[animation_level]">
                    @foreach ([
                        'none' => __('settings.theme_animation_none'),
                        'subtle' => __('settings.theme_animation_subtle'),
                        'rich' => __('settings.theme_animation_rich'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['animation_level'] ?? 'subtle') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <small class="text-muted">{{ __('settings.theme_animation_help') }}</small>
            </div>
        </div>
    </div>

    {{-- Filter Sections --}}
    <div class="card mb-3">
        <div class="card-header"><strong>{{ __('settings.theme_filter_sections') }}</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('settings.theme_filter_style') }}</label>
                <select class="form-select" name="content_config[filter_style]">
                    @foreach ([
                        'compact' => __('settings.theme_filter_compact'),
                        'card' => __('settings.theme_filter_card'),
                        'bordered' => __('settings.theme_filter_bordered'),
                        'inline' => __('settings.theme_filter_inline'),
                        'collapsible' => __('settings.theme_filter_collapsible'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['filter_style'] ?? 'compact') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <small class="text-muted">{{ __('settings.theme_filter_help') }}</small>
            </div>
        </div>
    </div>

    {{-- Content Display --}}
    <div class="card mb-3">
        <div class="card-header"><strong>{{ __('settings.theme_content_display') }}</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('settings.theme_content_display_style') }}</label>
                <select class="form-select" name="content_config[content_display_style]">
                    @foreach ([
                        'card' => __('settings.theme_content_card'),
                        'table' => __('settings.theme_content_table'),
                        'grid' => __('settings.theme_content_grid'),
                        'dashboard' => __('settings.theme_content_dashboard'),
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ ($content_config['content_display_style'] ?? 'card') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">{{ __('settings.theme_save') }}</button>
</form>
