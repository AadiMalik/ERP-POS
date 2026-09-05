<form id="inventorySettingForm">
    @csrf
    <div class="row">
        <div class="col-md-12">
            <h4>{{ __('settings.inventory_title') }}</h4>
            <hr>
        </div>
        @foreach ([
        'stock_tracking' => __('settings.stock_tracking'),
        'negative_stock' => __('settings.negative_stock'),
        'low_stock_alert' => __('settings.low_stock_alert'),
        'enable_batch_no' => __('settings.enable_batch_no'),
        'enable_expiry_date' => __('settings.enable_expiry_date'),
        'block_expired_sale' => __('settings.block_expired_sale'),
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
            <label>{{ __('settings.low_stock_quantity') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control" name="low_stock_quantity" value="{{ $inventory_setting->low_stock_quantity }}">
        </div>
        <div class="col-md-6">
            <label>{{ __('settings.batch_selection_strategy') }}</label>
            <select class="form-select" name="batch_selection_strategy">
                <option value="fefo" {{ ($inventory_setting->batch_selection_strategy ?? 'fefo') == 'fefo' ? 'selected' : '' }}>{{ __('settings.batch_strategy_fefo') }}</option>
                <option value="fifo" {{ ($inventory_setting->batch_selection_strategy ?? 'fefo') == 'fifo' ? 'selected' : '' }}>{{ __('settings.batch_strategy_fifo') }}</option>
            </select>
        </div>
        <div class="col-md-6">
            <label>{{ __('settings.near_expiry_days') }}</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control" name="near_expiry_days" value="{{ $inventory_setting->near_expiry_days ?? 30 }}">
        </div>
        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary" id="btnInventorySetting"
                    onclick="saveSetting('#inventorySettingForm','{{ url('admin/setting/inventory') }}')">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</form>
