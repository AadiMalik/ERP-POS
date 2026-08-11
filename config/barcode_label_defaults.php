<?php

/**
 * Default label design used to seed a business's barcode_settings.label_config
 * the first time it's read (SettingService::getBarcodeSetting()), the same way
 * config/print_defaults.php seeds print_settings.
 */

return [
    'size' => '40x25',
    'width_mm' => 40,
    'height_mm' => 25,
    'columns_per_row' => 3,
    'spacing_mm' => 2,
    'alignment' => 'center',
    'font_size_pt' => 8,

    'show_product_name' => true,
    'show_variation_name' => true,
    'show_sku' => true,
    'show_barcode' => true,
    'show_barcode_value_text' => true,
    'show_qr_code' => false,
];
