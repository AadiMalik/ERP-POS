<?php

/**
 * Default field visibility/config for the business-level thermal receipt.
 *
 * SettingService::getThermalPrintSetting() seeds a business's
 * thermal_print_settings row with this content the first time it's read,
 * so every business automatically gets this layout until it saves its own
 * customizations on the Thermal Print tab of the Business Settings page.
 */

return [

    'paper_width_mm' => 80,

    'field_config' => [
        // Business / branch info
        'branch_logo'          => true,
        'branch_name'          => true,
        'email'                => true,
        'phone'                => true,
        'address'              => true,
        'business_ntn'         => false,

        // Order info
        'customer_name'        => true,
        'order_type'           => true,
        'order_no'             => true,
        'date_time'            => true,
        'order_source'         => false,
        'order_taker_name'     => true,
        'sale_type'            => true,

        // Item table columns (product name + variation name are always shown)
        'quantity'              => true,
        'unit'                  => true,
        'unit_price'            => true,
        'line_total'            => true,
        'item_sale_type'        => true,

        // Totals
        'subtotal'              => true,
        'discount'              => true,
        'tax'                   => true,
        'voucher'               => true,
        'total'                 => true,

        // Payment
        'paid_amount'           => true,
        'due_amount'            => true,
        'payment_status'        => true,

        // Footer
        'thank_you_note'        => true,
        'qr_code'               => false,
        'powered_by_smart_mart' => true,
    ],

    'footer_config' => [
        'thank_you_note'  => 'Thank you for shopping with us!',
        'qr_data_source'  => 'order_no', // order_no | order_url | custom
        'qr_custom_text'  => null,
    ],

];
