<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">Paper Size</label>
        <select class="form-select" name="page_config[paper_size]">
            @foreach (['A4', 'A5', 'Letter', 'Legal', 'Thermal', 'Custom'] as $size)
                <option value="{{ $size }}" {{ ($page['paper_size'] ?? 'A4') === $size ? 'selected' : '' }}>
                    {{ $size }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Orientation</label>
        <select class="form-select" name="page_config[orientation]">
            <option value="" {{ empty($page['orientation'] ?? null) ? 'selected' : '' }}>Document default</option>
            <option value="portrait" {{ ($page['orientation'] ?? '') === 'portrait' ? 'selected' : '' }}>Portrait</option>
            <option value="landscape" {{ ($page['orientation'] ?? '') === 'landscape' ? 'selected' : '' }}>Landscape</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Custom Width (mm)</label>
        <input type="number" class="form-control" name="page_config[custom_width_mm]"
            value="{{ $page['custom_width_mm'] ?? '' }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Custom Height (mm)</label>
        <input type="number" class="form-control" name="page_config[custom_height_mm]"
            value="{{ $page['custom_height_mm'] ?? '' }}">
    </div>
</div>

<hr>
<h6>Margins (mm)</h6>
<div class="row g-3">
    <div class="col-md-2">
        <label class="form-label">Top</label>
        <input type="number" class="form-control" name="page_config[margin_top_mm]"
            value="{{ $page['margin_top_mm'] ?? 15 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Bottom</label>
        <input type="number" class="form-control" name="page_config[margin_bottom_mm]"
            value="{{ $page['margin_bottom_mm'] ?? 20 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Left</label>
        <input type="number" class="form-control" name="page_config[margin_left_mm]"
            value="{{ $page['margin_left_mm'] ?? 12 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Right</label>
        <input type="number" class="form-control" name="page_config[margin_right_mm]"
            value="{{ $page['margin_right_mm'] ?? 12 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Header Margin</label>
        <input type="number" class="form-control" name="page_config[header_margin_mm]"
            value="{{ $page['header_margin_mm'] ?? 0 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Footer Margin</label>
        <input type="number" class="form-control" name="page_config[footer_margin_mm]"
            value="{{ $page['footer_margin_mm'] ?? 0 }}">
    </div>
</div>

<hr>
<h6>Typography &amp; Scale</h6>
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">Font Family</label>
        <input type="text" class="form-control" name="page_config[font_family]"
            value="{{ $page['font_family'] ?? 'Arial, Helvetica, sans-serif' }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Base Font Size (px)</label>
        <input type="number" class="form-control" name="page_config[base_font_size_pt]"
            value="{{ $page['base_font_size_pt'] ?? 12 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Line Height</label>
        <input type="number" step="0.1" class="form-control" name="page_config[line_height]"
            value="{{ $page['line_height'] ?? 1.4 }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Page Scale (%)</label>
        <input type="number" class="form-control" name="page_config[page_scale_percent]"
            value="{{ $page['page_scale_percent'] ?? 100 }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Logo Size (px, max W x H)</label>
        <div class="d-flex gap-2">
            <input type="number" class="form-control" name="page_config[logo_max_width_px]"
                value="{{ $page['logo_max_width_px'] ?? 60 }}">
            <input type="number" class="form-control" name="page_config[logo_max_height_px]"
                value="{{ $page['logo_max_height_px'] ?? 60 }}">
        </div>
    </div>
</div>

<hr>
<h6>Table &amp; Print Behavior</h6>
<div class="row g-3">
    <div class="col-md-3">
        <div class="form-check">
            <input type="hidden" name="page_config[show_grid_lines]" value="0">
            <input class="form-check-input" type="checkbox" name="page_config[show_grid_lines]" value="1"
                {{ ($page['show_grid_lines'] ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Show Grid Lines</label>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-check">
            <input type="hidden" name="page_config[repeat_table_header]" value="0">
            <input class="form-check-input" type="checkbox" name="page_config[repeat_table_header]" value="1"
                {{ ($page['repeat_table_header'] ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Repeat Table Header on Every Page</label>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-check">
            <input type="hidden" name="page_config[print_background_colors]" value="0">
            <input class="form-check-input" type="checkbox" name="page_config[print_background_colors]" value="1"
                {{ ($page['print_background_colors'] ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Print Background Colors</label>
        </div>
    </div>
    <div class="col-md-3">
        <label class="form-label">Logo Scaling</label>
        <select class="form-select" name="page_config[logo_scaling]">
            @foreach (['contain', 'cover', 'fill'] as $mode)
                <option value="{{ $mode }}" {{ ($page['logo_scaling'] ?? 'contain') === $mode ? 'selected' : '' }}>
                    {{ ucfirst($mode) }}</option>
            @endforeach
        </select>
    </div>
</div>
