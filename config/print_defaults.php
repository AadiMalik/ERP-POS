<?php

/**
 * Built-in "Smart Mart ERP Default" print layout.
 *
 * This is the single source of truth for what the original hardcoded
 * print.css / print partials looked like. SettingService::getPrintSetting()
 * seeds a business's print_settings row with this content the first time it's
 * read, so every business automatically gets this layout until it saves its
 * own customizations on the Print Settings tab of the Business Settings page.
 */

return [

    'header' => [
        'fields' => [
            'logo'                  => ['visible' => true,  'order' => 1,  'align' => 'left',  'font_size' => null, 'font_weight' => null,   'color' => null],
            'company_name'          => ['visible' => true,  'order' => 2,  'align' => 'left',  'font_size' => 16,   'font_weight' => 'bold', 'color' => '#1a1a1a'],
            'branch_name'           => ['visible' => true,  'order' => 3,  'align' => 'left',  'font_size' => 11,   'font_weight' => 'normal', 'color' => '#444444'],
            'address'               => ['visible' => true,  'order' => 4,  'align' => 'left',  'font_size' => 11,   'font_weight' => 'normal', 'color' => '#444444'],
            'phone'                 => ['visible' => true,  'order' => 5,  'align' => 'left',  'font_size' => 11,   'font_weight' => 'normal', 'color' => '#444444'],
            'email'                 => ['visible' => true,  'order' => 5,  'align' => 'left',  'font_size' => 11,   'font_weight' => 'normal', 'color' => '#444444'],
            'website'               => ['visible' => false, 'order' => 6,  'align' => 'left',  'font_size' => 11,   'font_weight' => 'normal', 'color' => '#444444'],
            'ntn'                   => ['visible' => false, 'order' => 7,  'align' => 'left',  'font_size' => 10,   'font_weight' => 'normal', 'color' => '#444444'],
            'strn'                  => ['visible' => false, 'order' => 8,  'align' => 'left',  'font_size' => 10,   'font_weight' => 'normal', 'color' => '#444444'],
            'tax_reg_no'            => ['visible' => false, 'order' => 9,  'align' => 'left',  'font_size' => 10,   'font_weight' => 'normal', 'color' => '#444444'],
            'currency'              => ['visible' => false, 'order' => 10, 'align' => 'left',  'font_size' => 10,   'font_weight' => 'normal', 'color' => null],

            'document_title'        => ['visible' => true,  'order' => 1,  'align' => 'right', 'font_size' => 18,   'font_weight' => 'bold', 'color' => '#1a1a1a'],
            'document_no'           => ['visible' => true,  'order' => 2,  'align' => 'right', 'font_size' => 11,   'font_weight' => 'normal', 'color' => '#1a1a1a'],
            'voucher_no'            => ['visible' => false, 'order' => 3,  'align' => 'right', 'font_size' => 11,   'font_weight' => 'normal', 'color' => '#1a1a1a'],
            'reference_no'          => ['visible' => false, 'order' => 4,  'align' => 'right', 'font_size' => 11,   'font_weight' => 'normal', 'color' => '#1a1a1a'],
            'date'                  => ['visible' => true,  'order' => 5,  'align' => 'right', 'font_size' => 11,   'font_weight' => 'normal', 'color' => '#1a1a1a'],
            'time'                  => ['visible' => false, 'order' => 6,  'align' => 'right', 'font_size' => 11,   'font_weight' => 'normal', 'color' => '#1a1a1a'],

            'printed_by'            => ['visible' => true,  'order' => 1,  'align' => 'right', 'font_size' => 10,   'font_weight' => 'normal', 'color' => '#777777'],
            'printed_on'            => ['visible' => true,  'order' => 2,  'align' => 'right', 'font_size' => 10,   'font_weight' => 'normal', 'color' => '#777777'],

            'status_badge'          => ['visible' => true,  'order' => 1,  'align' => 'right', 'font_size' => 11,   'font_weight' => '800',  'color' => null],
            'posting_status_badge'  => ['visible' => true,  'order' => 2,  'align' => 'right', 'font_size' => 11,   'font_weight' => '800',  'color' => null],
            'approval_status'       => ['visible' => false, 'order' => 3,  'align' => 'right', 'font_size' => 11,   'font_weight' => '800',  'color' => null],

            'qr_code'               => ['visible' => false, 'order' => 11, 'align' => 'right', 'font_size' => null, 'font_weight' => null,   'color' => null, 'data_source' => 'document_url'],
            'barcode'               => ['visible' => false, 'order' => 12, 'align' => 'right', 'font_size' => null, 'font_weight' => null,   'color' => null, 'data_source' => 'document_no'],
        ],
        'watermark' => [
            'visible' => false,
            'text' => 'ORIGINAL',
            'color' => '#cccccc',
            'opacity' => 0.15,
            'font_size' => 60,
            'rotation_deg' => -30,
        ],
    ],

    'footer' => [
        'sections' => [
            'footer_notes'         => ['visible' => false, 'text' => null],
            'thank_you_message'    => ['visible' => false, 'text' => null],
            'terms_and_conditions' => ['visible' => false, 'text' => null],
            'return_policy'        => ['visible' => false, 'text' => null],
            'payment_instructions' => ['visible' => false, 'text' => null],
            'bank_details'         => ['visible' => false, 'text' => null],
            'contact_info'         => ['visible' => false, 'text' => null],
            'website'              => ['visible' => false, 'text' => null],
            'social_links'         => ['visible' => false, 'text' => null],
            'confidential_notice'  => ['visible' => false, 'text' => 'This document is confidential.'],
            'custom_text_block'    => ['visible' => false, 'text' => null],
        ],
        'page_numbers'     => ['visible' => false, 'format' => 'Page {page} of {total}'],
        'printed_datetime' => ['visible' => true],
        'signature_lines'  => ['visible' => true, 'labels' => []],
        'authorized_by'    => ['visible' => false, 'label' => 'Authorized By'],
        'received_by'      => ['visible' => false, 'label' => 'Received By'],
    ],

    'page' => [
        'paper_size' => 'A4',
        'orientation' => null,
        'custom_width_mm' => null,
        'custom_height_mm' => null,
        'margin_top_mm' => 15,
        'margin_bottom_mm' => 20,
        'margin_left_mm' => 12,
        'margin_right_mm' => 12,
        'header_margin_mm' => 0,
        'footer_margin_mm' => 0,
        'page_scale_percent' => 100,
        'font_family' => 'Arial, Helvetica, sans-serif',
        'base_font_size_pt' => 12,
        'line_height' => 1.4,
        'show_grid_lines' => true,
        'repeat_table_header' => true,
        'print_background_colors' => true,
        'logo_max_width_px' => 60,
        'logo_max_height_px' => 60,
        'logo_scaling' => 'contain',
    ],

    'body' => [
        'columns' => [],
        'grouping' => null,
        'decimal_points_override' => null,
        '_phase' => 1,
    ],

];
