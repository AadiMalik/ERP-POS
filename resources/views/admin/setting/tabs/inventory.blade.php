<form id="inventorySettingForm">
    @csrf
    <div class="row">
        <div class="col-md-12">
            <h4>Inventory Setting</h4>
            <hr>
        </div>
        @foreach ([
        'stock_tracking' => 'Stock Tracking',
        'negative_stock' => 'Negative Stock',
        'low_stock_alert' => 'Low Stock Alert',
        'auto_generate_sku' => 'Auto SKU',
        'enable_batch_no' => 'Batch No',
        'enable_expiry_date' => 'Expiry Date',
    ] as $field => $label)
            <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="{{ $field }}"
                        {{ $inventory_setting->$field ? 'checked' : '' }}>
                    <label>{{ $label }}</label>
                </div>
            </div>
        @endforeach
        <div class="col-md-6">
            <label>Low Stock Quantity</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control" name="low_stock_quantity" value="{{ $inventory_setting->low_stock_quantity }}">
        </div>
        @php
            $barcode_types = [
                'CODE128' => 'CODE128',
                'CODE39' => 'CODE39',
                'EAN13' => 'EAN13',
                'EAN8' => 'EAN8',
                'UPC' => 'UPC',
                'UPCE' => 'UPCE',
                'ITF14' => 'ITF14',
                'CODABAR' => 'CODABAR',
                'MSI' => 'MSI',
                'PHARMACODE' => 'PHARMACODE',
                'POSTNET' => 'POSTNET',
                'QRCODE' => 'QRCODE',
                'DATAMATRIX' => 'DATAMATRIX',
            ];
        @endphp
        <div class="col-md-6">
            <label>Barcode Type</label>
            <select class="form-select select2" name="barcode_type">
                @foreach ($barcode_types as $key => $value)
                    <option value="{{ $key }}"
                        {{ $inventory_setting->barcode_type == $key ? 'selected' : '' }}>
                        {{ $value }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary" id="btnInventorySetting">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>
