<?php

/**
 * Built-in Theme Style presets ("Theme / Appearance" in Business Settings).
 *
 * Each preset is a complete visual identity - colors, sidebar/header/footer
 * behavior, and the full set of content_config axes (card shape, radius,
 * shadow, table/button/form/filter styling, and animation level) - so
 * switching styles changes the ERP's whole look, not just its accent color.
 *
 * 'sneat_default' (Style 1 - Dukanaz Modern) is the seeded default for
 * every business that has not opened the Theme tab yet, and reproduces the
 * reference Dukanaz ERP dashboard look (light sidebar, solid active pill,
 * pastel gradient KPI cards, colorful charts).
 */
return [

    'sneat_default' => [
        'label'           => 'Style 1 · Dukanaz Modern',
        'primary_color'   => '#3833C8',
        'secondary_color' => '#64748b',
        'accent_color'    => '#2DD4BF',
        'font_family'     => "'Public Sans', sans-serif",
        'font_size_base'  => 'md',
        'sidebar_config'  => [
            'skin'               => 'light',
            'width'              => 'default',
            'collapsed_behavior' => 'expanded',
            'position'           => 'fixed',
        ],
        'header_config'   => [
            'style'    => 'light',
            'position' => 'sticky',
            'type'     => 'full',
        ],
        'footer_config'   => [
            'visible' => true,
            'sticky'  => false,
            'style'   => 'light',
        ],
        'content_config'  => [
            'background'            => 'light',
            'spacing'               => 'comfortable',
            'card_style'            => 'gradient',
            'border_radius'         => 'lg',
            'shadow_level'          => 'md',
            'table_style'           => 'striped',
            'button_style'          => 'rounded',
            'form_style'            => 'rounded',
            'filter_style'          => 'card',
            'content_display_style' => 'dashboard',
            'animation_level'       => 'rich',
        ],
    ],

    'corporate_blue' => [
        'label'           => 'Style 2 · Corporate Blue',
        'primary_color'   => '#2563eb',
        'secondary_color' => '#64748b',
        'accent_color'    => '#0ea5e9',
        'font_family'     => "'Public Sans', sans-serif",
        'font_size_base'  => 'md',
        'sidebar_config'  => [
            'skin'               => 'dark',
            'width'              => 'default',
            'collapsed_behavior' => 'expanded',
            'position'           => 'fixed',
        ],
        'header_config'   => [
            'style'    => 'light',
            'position' => 'sticky',
            'type'     => 'full',
        ],
        'footer_config'   => [
            'visible' => true,
            'sticky'  => false,
            'style'   => 'light',
        ],
        'content_config'  => [
            'background'            => 'light',
            'spacing'               => 'comfortable',
            'card_style'            => 'bordered',
            'border_radius'         => 'sm',
            'shadow_level'          => 'sm',
            'table_style'           => 'bordered',
            'button_style'          => 'default',
            'form_style'            => 'default',
            'filter_style'          => 'card',
            'content_display_style' => 'card',
            'animation_level'       => 'subtle',
        ],
    ],

    'emerald_finance' => [
        'label'           => 'Style 3 · Emerald Finance',
        'primary_color'   => '#0f9d58',
        'secondary_color' => '#6b7280',
        'accent_color'    => '#f59e0b',
        'font_family'     => "'Public Sans', sans-serif",
        'font_size_base'  => 'md',
        'sidebar_config'  => [
            'skin'               => 'light',
            'width'              => 'compact',
            'collapsed_behavior' => 'expanded',
            'position'           => 'fixed',
        ],
        'header_config'   => [
            'style'    => 'colored',
            'position' => 'sticky',
            'type'     => 'detached',
        ],
        'footer_config'   => [
            'visible' => true,
            'sticky'  => false,
            'style'   => 'light',
        ],
        'content_config'  => [
            'background'            => 'default',
            'spacing'               => 'comfortable',
            'card_style'            => 'shadow',
            'border_radius'         => 'lg',
            'shadow_level'          => 'md',
            'table_style'           => 'striped',
            'button_style'          => 'rounded',
            'form_style'            => 'rounded',
            'filter_style'          => 'bordered',
            'content_display_style' => 'dashboard',
            'animation_level'       => 'subtle',
        ],
    ],

    'slate_dark' => [
        'label'           => 'Style 4 · Slate Dark',
        'primary_color'   => '#7c8cf8',
        'secondary_color' => '#94a3b8',
        'accent_color'    => '#22d3ee',
        'font_family'     => "'Public Sans', sans-serif",
        'font_size_base'  => 'md',
        'sidebar_config'  => [
            'skin'               => 'dark',
            'width'              => 'default',
            'collapsed_behavior' => 'expanded',
            'position'           => 'fixed',
        ],
        'header_config'   => [
            'style'    => 'dark',
            'position' => 'sticky',
            'type'     => 'full',
        ],
        'footer_config'   => [
            'visible' => true,
            'sticky'  => false,
            'style'   => 'dark',
        ],
        'content_config'  => [
            'background'            => 'dark',
            'spacing'               => 'comfortable',
            'card_style'            => 'flat',
            'border_radius'         => 'md',
            'shadow_level'          => 'none',
            'table_style'           => 'borderless',
            'button_style'          => 'default',
            'form_style'            => 'default',
            'filter_style'          => 'inline',
            'content_display_style' => 'card',
            'animation_level'       => 'subtle',
        ],
    ],

    'sunset_amber' => [
        'label'           => 'Style 5 · Sunset Amber',
        'primary_color'   => '#f97316',
        'secondary_color' => '#78716c',
        'accent_color'    => '#e11d48',
        'font_family'     => "'Public Sans', sans-serif",
        'font_size_base'  => 'md',
        'sidebar_config'  => [
            'skin'               => 'gradient',
            'width'              => 'default',
            'collapsed_behavior' => 'expanded',
            'position'           => 'fixed',
        ],
        'header_config'   => [
            'style'    => 'colored',
            'position' => 'sticky',
            'type'     => 'detached',
        ],
        'footer_config'   => [
            'visible' => true,
            'sticky'  => false,
            'style'   => 'light',
        ],
        'content_config'  => [
            'background'            => 'default',
            'spacing'               => 'compact',
            'card_style'            => 'gradient',
            'border_radius'         => 'lg',
            'shadow_level'          => 'md',
            'table_style'           => 'striped',
            'button_style'          => 'pill',
            'form_style'            => 'rounded',
            'filter_style'          => 'collapsible',
            'content_display_style' => 'grid',
            'animation_level'       => 'rich',
        ],
    ],

];
