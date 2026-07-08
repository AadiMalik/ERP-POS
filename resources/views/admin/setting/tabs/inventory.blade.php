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
            <input class="form-control" name="low_stock_quantity" value="{{ $inventory_setting->low_stock_quantity }}">
        </div>
        <div class="col-md-6">
            <label>Barcode Type</label>
            <input class="form-control" name="barcode_type" value="{{ $inventory_setting->barcode_type }}">
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
