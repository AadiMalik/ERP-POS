<?php

/**
 * Built-in Theme / Appearance presets.
 *
 * 'sneat_default' intentionally mirrors the application's current hardcoded
 * look (colors, skins, layout behavior) so that businesses which have never
 * opened the Theme setting tab keep rendering exactly as before.
 */
return [

    'sneat_default' => [
        'label'           => 'Sneat Default',
        'primary_color'   => '#696cff',
        'secondary_color' => '#8592a3',
        'accent_color'    => '#03c3ec',
        'font_family'     => "'Public Sans', sans-serif",
        'font_size_base'  => 'md',
        'sidebar_config'  => [
            'skin'               => 'light',
            'width'              => 'default',
            'collapsed_behavior' => 'expanded',
            'position'           => 'static',
        ],
        'header_config'   => [
            'style'    => 'light',
            'position' => 'static',
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
            'border_radius'         => 'md',
            'shadow_level'          => 'sm',
            'table_style'           => 'default',
            'button_style'          => 'default',
            'form_style'            => 'default',
            'filter_style'          => 'compact',
            'content_display_style' => 'card',
        ],
    ],

    'corporate_blue' => [
        'label'           => 'Corporate Blue',
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
        ],
    ],

    'emerald_finance' => [
        'label'           => 'Emerald Finance',
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
        ],
    ],

    'slate_dark' => [
        'label'           => 'Slate Dark',
        'primary_color'   => '#7c8cf8',
        'secondary_color' => '#94a3b8',
        'accent_color'    => '#22d3ee',
        'font_family'     => "'Public Sans', sans-serif",
        'font_size_base'  => 'md',
        'sidebar_config'  => [
            'skin'               => 'dark',
            'width'              => 'default',
            'collapsed_behavior' => 'hover',
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
        ],
    ],

    'sunset_amber' => [
        'label'           => 'Sunset Amber',
        'primary_color'   => '#f97316',
        'secondary_color' => '#78716c',
        'accent_color'    => '#e11d48',
        'font_family'     => "'Public Sans', sans-serif",
        'font_size_base'  => 'md',
        'sidebar_config'  => [
            'skin'               => 'gradient',
            'width'              => 'default',
            'collapsed_behavior' => 'expanded',
            'position'           => 'static',
        ],
        'header_config'   => [
            'style'    => 'colored',
            'position' => 'static',
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
            'card_style'            => 'shadow',
            'border_radius'         => 'lg',
            'shadow_level'          => 'md',
            'table_style'           => 'striped',
            'button_style'          => 'pill',
            'form_style'            => 'rounded',
            'filter_style'          => 'collapsible',
            'content_display_style' => 'grid',
        ],
    ],

    'minimal_gray' => [
        'label'           => 'Minimal Gray',
        'primary_color'   => '#4b5563',
        'secondary_color' => '#9ca3af',
        'accent_color'    => '#6366f1',
        'font_family'     => "'Public Sans', sans-serif",
        'font_size_base'  => 'sm',
        'sidebar_config'  => [
            'skin'               => 'light',
            'width'              => 'compact',
            'collapsed_behavior' => 'collapsed',
            'position'           => 'static',
        ],
        'header_config'   => [
            'style'    => 'light',
            'position' => 'static',
            'type'     => 'detached',
        ],
        'footer_config'   => [
            'visible' => false,
            'sticky'  => false,
            'style'   => 'light',
        ],
        'content_config'  => [
            'background'            => 'light',
            'spacing'               => 'compact',
            'card_style'            => 'flat',
            'border_radius'         => 'none',
            'shadow_level'          => 'none',
            'table_style'           => 'compact',
            'button_style'          => 'square',
            'form_style'            => 'flat',
            'filter_style'          => 'inline',
            'content_display_style' => 'table',
        ],
    ],

];
