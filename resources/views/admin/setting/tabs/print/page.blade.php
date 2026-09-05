<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">{{ __('settings.print_paper_size') }}</label>
        <select class="form-select" name="page_config[paper_size]">
            @foreach (['A4', 'A5', 'Letter', 'Legal', 'Thermal', 'Custom'] as $size)
                <option value="{{ $size }}" {{ ($page['paper_size'] ?? 'A4') === $size ? 'selected' : '' }}>
                    {{ $size }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('settings.print_orientation') }}</label>
        <select class="form-select" name="page_config[orientation]">
            <option value="" {{ empty($page['orientation'] ?? null) ? 'selected' : '' }}>{{ __('settings.print_orientation_document_default') }}</option>
            <option value="portrait" {{ ($page['orientation'] ?? '') === 'portrait' ? 'selected' : '' }}>{{ __('settings.print_orientation_portrait') }}</option>
            <option value="landscape" {{ ($page['orientation'] ?? '') === 'landscape' ? 'selected' : '' }}>{{ __('settings.print_orientation_landscape') }}</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('settings.print_custom_width_mm') }}</label>
        <input type="number" class="form-control" name="page_config[custom_width_mm]"
            value="{{ $page['custom_width_mm'] ?? '' }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('settings.print_custom_height_mm') }}</label>
        <input type="number" class="form-control" name="page_config[custom_height_mm]"
            value="{{ $page['custom_height_mm'] ?? '' }}">
    </div>
</div>

<hr>
<h6>{{ __('settings.print_margins') }}</h6>
<div class="row g-3">
    <div class="col-md-2">
        <label class="form-label">{{ __('settings.print_margin_top') }}</label>
        <input type="number" class="form-control" name="page_config[margin_top_mm]"
            value="{{ $page['margin_top_mm'] ?? 15 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('settings.print_margin_bottom') }}</label>
        <input type="number" class="form-control" name="page_config[margin_bottom_mm]"
            value="{{ $page['margin_bottom_mm'] ?? 20 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('settings.print_margin_left') }}</label>
        <input type="number" class="form-control" name="page_config[margin_left_mm]"
            value="{{ $page['margin_left_mm'] ?? 12 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('settings.print_margin_right') }}</label>
        <input type="number" class="form-control" name="page_config[margin_right_mm]"
            value="{{ $page['margin_right_mm'] ?? 12 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('settings.print_header_margin') }}</label>
        <input type="number" class="form-control" name="page_config[header_margin_mm]"
            value="{{ $page['header_margin_mm'] ?? 0 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('settings.print_footer_margin') }}</label>
        <input type="number" class="form-control" name="page_config[footer_margin_mm]"
            value="{{ $page['footer_margin_mm'] ?? 0 }}">
    </div>
</div>

<hr>
<h6>{{ __('settings.print_typography_scale') }}</h6>
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">{{ __('settings.print_font_family') }}</label>
        <input type="text" class="form-control" name="page_config[font_family]"
            value="{{ $page['font_family'] ?? 'Arial, Helvetica, sans-serif' }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('settings.print_base_font_size') }}</label>
        <input type="number" class="form-control" name="page_config[base_font_size_pt]"
            value="{{ $page['base_font_size_pt'] ?? 12 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('settings.print_line_height') }}</label>
        <input type="number" step="0.1" class="form-control" name="page_config[line_height]"
            value="{{ $page['line_height'] ?? 1.4 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('settings.print_page_scale') }}</label>
        <input type="number" class="form-control" name="page_config[page_scale_percent]"
            value="{{ $page['page_scale_percent'] ?? 100 }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('settings.print_logo_size') }}</label>
        <div class="d-flex gap-2">
            <input type="number" class="form-control" name="page_config[logo_max_width_px]"
                value="{{ $page['logo_max_width_px'] ?? 60 }}">
            <input type="number" class="form-control" name="page_config[logo_max_height_px]"
                value="{{ $page['logo_max_height_px'] ?? 60 }}">
        </div>
    </div>
</div>

<hr>
<h6>{{ __('settings.print_table_behavior') }}</h6>
<div class="row g-3">
    <div class="col-md-3">
        <div class="form-check">
            <input type="hidden" name="page_config[show_grid_lines]" value="0">
            <input class="form-check-input" type="checkbox" name="page_config[show_grid_lines]" value="1"
                {{ ($page['show_grid_lines'] ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">{{ __('settings.print_show_grid_lines') }}</label>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-check">
            <input type="hidden" name="page_config[repeat_table_header]" value="0">
            <input class="form-check-input" type="checkbox" name="page_config[repeat_table_header]" value="1"
                {{ ($page['repeat_table_header'] ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">{{ __('settings.print_repeat_table_header') }}</label>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-check">
            <input type="hidden" name="page_config[print_background_colors]" value="0">
            <input class="form-check-input" type="checkbox" name="page_config[print_background_colors]" value="1"
                {{ ($page['print_background_colors'] ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">{{ __('settings.print_background_colors') }}</label>
        </div>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('settings.print_logo_scaling') }}</label>
        <select class="form-select" name="page_config[logo_scaling]">
            @foreach ([
                'contain' => __('settings.print_logo_scaling_contain'),
                'cover' => __('settings.print_logo_scaling_cover'),
                'fill' => __('settings.print_logo_scaling_fill'),
            ] as $mode => $label)
                <option value="{{ $mode }}" {{ ($page['logo_scaling'] ?? 'contain') === $mode ? 'selected' : '' }}>
                    {{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
