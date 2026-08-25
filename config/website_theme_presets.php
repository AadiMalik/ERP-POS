<?php

/**
 * Built-in storefront ("Website Theme") presets — Business Settings > Website Theme.
 *
 * Each of the 6 presets mirrors one of the Vue frontend's existing
 * src/themes/theme{N} designs 1:1 (colors pulled straight from
 * src/styles/themes/theme{N}.css) so selecting a preset here reproduces that
 * theme's current look exactly. The admin can then override individual
 * colors/typography/button style afterward - selecting a preset always loads
 * its full defaults into the editable form first.
 *
 * font_pairings / button_styles / typography_style scales are small closed
 * sets (not free text) per CLAUDE.md's "no unlimited CSS builder" rule -
 * the storefront only ever receives one of these named, pre-approved values.
 */
return [

    'themes' => [

        'theme1' => [
            'label'       => 'Classic Market',
            'description' => 'Fresh & trusted grocery classic',
            'colors' => [
                'primary'    => '#1E9E5A',
                'secondary'  => '#0B3D2E',
                'accent'     => '#FF6B35',
                'background' => '#FFFFFF',
                'surface'    => '#FFFFFF',
                'text'       => '#16241C',
                'heading'    => '#16241C',
                'border'     => '#E7ECE9',
                'success'    => '#1E9E5A',
                'warning'    => '#FFB020',
                'error'      => '#E5484D',
            ],
            'font_pairing'      => 'poppins_jakarta',
            'font_size_base'    => 'md',
            'button_style'      => 'soft_pill',
            'typography_style'  => 'comfortable',
        ],

        'theme2' => [
            'label'       => 'Vibrant Bazaar',
            'description' => 'Bold, colorful & energetic',
            'colors' => [
                'primary'    => '#7C3AED',
                'secondary'  => '#1E1533',
                'accent'     => '#FB923C',
                'background' => '#FFFFFF',
                'surface'    => '#FFFFFF',
                'text'       => '#1E1533',
                'heading'    => '#1E1533',
                'border'     => '#EBE1FB',
                'success'    => '#16A34A',
                'warning'    => '#F472B6',
                'error'      => '#E11D48',
            ],
            'font_pairing'      => 'outfit_rubik',
            'font_size_base'    => 'md',
            'button_style'      => 'bold_pill',
            'typography_style'  => 'compact',
        ],

        'theme3' => [
            'label'       => 'Luxury Edit',
            'description' => 'Elegant, spacious & editorial',
            'colors' => [
                'primary'    => '#1C1917',
                'secondary'  => '#1C1917',
                'accent'     => '#A16207',
                'background' => '#FAFAF9',
                'surface'    => '#FFFFFF',
                'text'       => '#1C1917',
                'heading'    => '#1C1917',
                'border'     => '#E3DFD8',
                'success'    => '#166534',
                'warning'    => '#CA8A04',
                'error'      => '#B91C1C',
            ],
            'font_pairing'      => 'playfair_inter',
            'font_size_base'    => 'md',
            'button_style'      => 'sharp_minimal',
            'typography_style'  => 'relaxed',
        ],

        'theme4' => [
            'label'       => 'Fresh Block',
            'description' => 'Bold, brutalist & editorial',
            'colors' => [
                'primary'    => '#0F7A3D',
                'secondary'  => '#111827',
                'accent'     => '#FF5722',
                'background' => '#FBFAF7',
                'surface'    => '#FFFFFF',
                'text'       => '#111827',
                'heading'    => '#111827',
                'border'     => '#111827',
                'success'    => '#0F7A3D',
                'warning'    => '#FFC107',
                'error'      => '#DC2626',
            ],
            'font_pairing'      => 'rubik_nunito',
            'font_size_base'    => 'md',
            'button_style'      => 'hard_block',
            'typography_style'  => 'compact',
        ],

        'theme5' => [
            'label'       => 'Atelier',
            'description' => 'Clean, quiet & minimal boutique',
            'colors' => [
                'primary'    => '#14181A',
                'secondary'  => '#14181A',
                'accent'     => '#A6803C',
                'background' => '#FAF9F6',
                'surface'    => '#FFFFFF',
                'text'       => '#1B1D1E',
                'heading'    => '#1B1D1E',
                'border'     => '#E4E0D6',
                'success'    => '#3A6B4C',
                'warning'    => '#A6803C',
                'error'      => '#B3261E',
            ],
            'font_pairing'      => 'montserrat',
            'font_size_base'    => 'md',
            'button_style'      => 'hairline_pill',
            'typography_style'  => 'relaxed',
        ],

        'theme6' => [
            'label'       => 'Bazaar Bento',
            'description' => 'Vibrant, playful marketplace',
            'colors' => [
                'primary'    => '#7C3AED',
                'secondary'  => '#4C1D95',
                'accent'     => '#16A34A',
                'background' => '#FBF9FF',
                'surface'    => '#FFFFFF',
                'text'       => '#2E1065',
                'heading'    => '#2E1065',
                'border'     => '#E3D6FB',
                'success'    => '#16A34A',
                'warning'    => '#F59E0B',
                'error'      => '#E11D48',
            ],
            'font_pairing'      => 'outfit_work',
            'font_size_base'    => 'md',
            'button_style'      => 'bento_pill',
            'typography_style'  => 'comfortable',
        ],

    ],

    // key => [font_display, font_body] - the exact CSS font-family stacks the
    // frontend's src/styles/themes/theme{N}.css already use.
    'font_pairings' => [
        'poppins_jakarta' => [
            'label'        => 'Poppins + Plus Jakarta Sans',
            'font_display' => "'Poppins', 'Segoe UI', sans-serif",
            'font_body'    => "'Plus Jakarta Sans', 'Segoe UI', sans-serif",
        ],
        'outfit_rubik' => [
            'label'        => 'Outfit + Rubik',
            'font_display' => "'Outfit', 'Segoe UI', sans-serif",
            'font_body'    => "'Rubik', 'Segoe UI', sans-serif",
        ],
        'playfair_inter' => [
            'label'        => 'Playfair Display + Inter',
            'font_display' => "'Playfair Display', Georgia, serif",
            'font_body'    => "'Inter', 'Segoe UI', sans-serif",
        ],
        'rubik_nunito' => [
            'label'        => 'Rubik + Nunito Sans',
            'font_display' => "'Rubik', 'Segoe UI', sans-serif",
            'font_body'    => "'Nunito Sans', 'Segoe UI', sans-serif",
        ],
        'montserrat' => [
            'label'        => 'Montserrat',
            'font_display' => "'Montserrat', 'Segoe UI', sans-serif",
            'font_body'    => "'Montserrat', 'Segoe UI', sans-serif",
        ],
        'outfit_work' => [
            'label'        => 'Outfit + Work Sans',
            'font_display' => "'Outfit', 'Segoe UI', sans-serif",
            'font_body'    => "'Work Sans', 'Segoe UI', sans-serif",
        ],
    ],

    // key => [radius, weight, shadow] applied to --radius-btn/--btn-weight/--shadow-card.
    'button_styles' => [
        'soft_pill'     => ['label' => 'Soft Pill',       'radius' => '999px', 'weight' => '600', 'shadow' => '0 2px 8px rgba(16, 34, 24, 0.06)'],
        'bold_pill'     => ['label' => 'Bold Pill',        'radius' => '999px', 'weight' => '700', 'shadow' => '0 3px 10px rgba(124, 58, 237, 0.10)'],
        'sharp_minimal' => ['label' => 'Sharp Minimal',    'radius' => '8px',   'weight' => '500', 'shadow' => '0 2px 6px rgba(28, 25, 23, 0.05)'],
        'hard_block'    => ['label' => 'Hard Block',       'radius' => '10px',  'weight' => '800', 'shadow' => '3px 3px 0 rgba(17, 24, 39, 0.9)'],
        'hairline_pill' => ['label' => 'Hairline Pill',    'radius' => '999px', 'weight' => '500', 'shadow' => 'none'],
        'bento_pill'    => ['label' => 'Bento Pill',       'radius' => '999px', 'weight' => '700', 'shadow' => '0 3px 10px rgba(124, 58, 237, 0.08)'],
    ],

    // key => [lh_heading, lh_body]
    'typography_styles' => [
        'compact'     => ['label' => 'Compact',     'lh_heading' => '1.05', 'lh_body' => '1.5'],
        'comfortable' => ['label' => 'Comfortable', 'lh_heading' => '1.2',  'lh_body' => '1.6'],
        'relaxed'     => ['label' => 'Relaxed',     'lh_heading' => '1.3',  'lh_body' => '1.75'],
    ],

    // key => root font-size percentage (scales every rem-based token together).
    'font_size_scale' => [
        'sm' => '94%',
        'md' => '100%',
        'lg' => '108%',
    ],

];
