<form id="posSettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>POS Configuration</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <label>Register Mode</label>
            <select class="form-select select2" name="register_mode">
                <option value="manual" {{ $pos_setting->register_mode == 'manual' ? 'selected' : '' }}>Manual</option>
                <option value="automatic" {{ $pos_setting->register_mode == 'automatic' ? 'selected' : '' }}>Automatic</option>
            </select>
        </div>

        <div class="col-md-3 mb-3 register-mode-automatic-field">
            <label>Default Open Time</label>
            <input type="time" class="form-control" name="open_time" value="{{ $pos_setting->open_time }}">
            <small class="text-muted">Automatic mode - overridden by each branch's own Open Time if set.</small>
        </div>

        <div class="col-md-3 mb-3 register-mode-automatic-field">
            <label>Default Close Time</label>
            <input type="time" class="form-control" name="close_time" value="{{ $pos_setting->close_time }}">
            <small class="text-muted">Automatic mode - overridden by each branch's own Close Time if set.</small>
        </div>

        <div class="col-md-6 mb-3">
            <label>Default Walk-in Customer</label>
            <select class="form-select select2" name="default_customer_user_id">
                <option value="">--Select Customer--</option>
                @foreach ($pos_customers as $customer)
                    <option value="{{ $customer->user_id }}"
                        {{ $pos_setting->default_customer_user_id == $customer->user_id ? 'selected' : '' }}>
                        {{ $customer->code }} - {{ $customer->user->name ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4 mb-3">
            <label>Invoice Prefix</label>
            <input type="text" class="form-control" name="invoice_prefix" value="{{ $pos_setting->invoice_prefix }}">
        </div>

        <div class="col-md-4 mb-3">
            <label>Invoice Start Number</label>
            <input type="number" class="form-control" name="invoice_start" value="{{ $pos_setting->invoice_start }}">
        </div>

        <div class="col-md-4 mb-3">
            <label>Daily Order ID Reset</label>
            <select class="form-select select2" name="daily_order_id_reset">
                <option value="daily" {{ $pos_setting->daily_order_id_reset == 'daily' ? 'selected' : '' }}>Daily</option>
                <option value="never" {{ $pos_setting->daily_order_id_reset == 'never' ? 'selected' : '' }}>Never</option>
            </select>
        </div>

        <div class="col-md-12 mb-3">
            <label>Invoice Footer</label>
            <textarea class="form-control" name="invoice_footer" rows="2">{{ $pos_setting->invoice_footer }}</textarea>
        </div>

        <div class="col-md-3 mb-3">
            <label class="d-block">Enable Discount</label>
            <input type="checkbox" class="form-check-input" name="enable_discount" value="1"
                {{ $pos_setting->enable_discount ? 'checked' : '' }}>
        </div>

        <div class="col-md-3 mb-3">
            <label>Discount Level</label>
            <select class="form-select select2" name="discount_level">
                <option value="line" {{ $pos_setting->discount_level == 'line' ? 'selected' : '' }}>Line only</option>
                <option value="order" {{ $pos_setting->discount_level == 'order' ? 'selected' : '' }}>Order only</option>
                <option value="both" {{ $pos_setting->discount_level == 'both' ? 'selected' : '' }}>Both</option>
            </select>
        </div>

        <div class="col-md-3 mb-3">
            <label class="d-block">Enable Hold Order</label>
            <input type="checkbox" class="form-check-input" name="enable_hold_order" value="1"
                {{ $pos_setting->enable_hold_order ? 'checked' : '' }}>
        </div>

        <div class="col-md-3 mb-3">
            <label class="d-block">Auto Print Invoice</label>
            <input type="checkbox" class="form-check-input" name="auto_print_invoice" value="1"
                {{ $pos_setting->auto_print_invoice ? 'checked' : '' }}>
        </div>

        <div class="col-md-3 mb-3">
            <label class="d-block">Show Product Image</label>
            <input type="checkbox" class="form-check-input" name="show_product_image" value="1"
                {{ $pos_setting->show_product_image ? 'checked' : '' }}>
        </div>

        <div class="col-md-3 mb-3">
            <label class="d-block">Allow Backdated Sale</label>
            <input type="checkbox" class="form-check-input pos-config-field" name="allow_backdated_sale" value="1"
                {{ $pos_setting->allow_backdated_sale ? 'checked' : '' }}>
        </div>

        <div class="col-md-3 mb-3">
            <label>Backdated Sale Max Days</label>
            <input type="number" class="form-control" name="backdated_sale_max_days" min="0"
                value="{{ $pos_setting->backdated_sale_max_days }}">
        </div>

        <div class="col-md-12">
            <hr>
            <h5>Return / Refund</h5>
        </div>

        <div class="col-md-4 mb-3">
            <label>Return Window (days)</label>
            <input type="number" class="form-control" name="return_window_days" min="0"
                value="{{ $pos_setting->return_window_days }}">
        </div>

        <div class="col-md-4 mb-3">
            <label class="d-block">Require Return Reason</label>
            <input type="checkbox" class="form-check-input" name="require_return_reason" value="1"
                {{ $pos_setting->require_return_reason ? 'checked' : '' }}>
        </div>

        <div class="col-md-4 mb-3">
            <label class="d-block">Allow Partial Return</label>
            <input type="checkbox" class="form-check-input" name="allow_partial_return" value="1"
                {{ $pos_setting->allow_partial_return ? 'checked' : '' }}>
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button"
                    class="btn btn-primary"
                    onclick="saveSetting('#posSettingForm','{{ url('admin/setting/pos') }}')">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>
