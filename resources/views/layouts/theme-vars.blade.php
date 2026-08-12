{{--
    Renders the CSS custom properties driving the whole ERP's appearance from
    the current business' theme settings (falls back to the "sneat_default"
    preset, which matches the app's original hardcoded look, whenever a
    business has not saved a theme yet). $theme is expected to already be
    computed by layouts/app.blade.php before this partial is included.
--}}
@php
    $theme = $theme ?? array_replace_recursive(
        config('theme_presets.sneat_default'),
        session('theme_setting') ?: []
    );

    $sidebar = $theme['sidebar_config'] ?? [];
    $header  = $theme['header_config'] ?? [];
    $footer  = $theme['footer_config'] ?? [];
    $content = $theme['content_config'] ?? [];

    $radiusMap = ['none' => '0', 'sm' => '.25rem', 'md' => '.5rem', 'lg' => '.75rem'];
    $shadowMap = [
        'none' => 'none',
        'sm'   => '0 1px 3px rgba(0, 0, 0, .08)',
        'md'   => '0 .25rem .75rem rgba(0, 0, 0, .1)',
        'lg'   => '0 .5rem 1.5rem rgba(0, 0, 0, .15)',
    ];
    $fontSizeMap = ['sm' => '13px', 'md' => '14px', 'lg' => '15px'];
    $sidebarWidthMap = ['compact' => '200px', 'wide' => '300px'];

    $hex2rgb = function ($hex) {
        $hex = ltrim($hex ?: '696cff', '#');
        if (strlen($hex) !== 6) {
            $hex = '696cff';
        }
        return implode(', ', array_map('hexdec', str_split($hex, 2)));
    };

    $primary   = $theme['primary_color'] ?? '#696cff';
    $secondary = $theme['secondary_color'] ?? '#8592a3';
    $accent    = $theme['accent_color'] ?? '#03c3ec';
@endphp
<style id="erp-theme-vars">
    :root {
        --erp-primary: {{ $primary }};
        --erp-primary-rgb: {{ $hex2rgb($primary) }};
        --erp-secondary: {{ $secondary }};
        --erp-secondary-rgb: {{ $hex2rgb($secondary) }};
        --erp-accent: {{ $accent }};
        --erp-accent-rgb: {{ $hex2rgb($accent) }};
        --erp-font-family: {{ $theme['font_family'] ?? "'Public Sans', sans-serif" }};
        --erp-font-size-base: {{ $fontSizeMap[$content['font_size_base'] ?? ($theme['font_size_base'] ?? 'md')] ?? '14px' }};
        --erp-radius: {{ $radiusMap[$content['border_radius'] ?? 'md'] ?? '.5rem' }};
        --erp-shadow: {{ $shadowMap[$content['shadow_level'] ?? 'sm'] ?? '0 1px 3px rgba(0, 0, 0, .08)' }};
        @if (isset($sidebarWidthMap[$sidebar['width'] ?? 'default']))
        --erp-sidebar-width: {{ $sidebarWidthMap[$sidebar['width']] }};
        @endif

        --bs-primary: var(--erp-primary);
        --bs-primary-rgb: var(--erp-primary-rgb);
        --bs-link-color: var(--erp-primary);
        --bs-link-hover-color: var(--erp-primary);
        --bs-body-font-family: var(--erp-font-family);
        --bs-secondary: var(--erp-secondary);
        --bs-secondary-rgb: var(--erp-secondary-rgb);
        --bs-info: var(--erp-accent);
        --bs-info-rgb: var(--erp-accent-rgb);
    }

    body {
        font-family: var(--erp-font-family);
        font-size: var(--erp-font-size-base);
    }
</style>
