<form id="supplierSettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>Supplier Setting</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <label>Supplier Code Prefix</label>
            <input type="text" class="form-control" name="supplier_code_prefix"
                value="{{ $supplier_setting->supplier_code_prefix }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>Enable Credit Limit</label>
            <select class="form-select select2" name="supplier_enable_credit_limit">
                <option value="1" {{ $supplier_setting->enable_credit_limit == 1 ? 'selected' : '' }}>
                    Yes
                </option>
                <option value="0" {{ $supplier_setting->enable_credit_limit == 0 ? 'selected' : '' }}>
                    No
                </option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>Default Credit Limit</label>
            <input type="text" class="form-control" name="supplier_credit_limit" onkeypress="return isNumberKey(event)"
                value="{{ $supplier_setting->credit_limit }}">
            <small class="text-muted">
                Default credit limit assigned to new suppliers.
            </small>
        </div>

        <div class="col-md-6 mb-3">
            <label>Default Payment Days</label>
            <input type="text" class="form-control" name="default_payment_days"
                onkeypress="return isNumberKey(event)" value="{{ $supplier_setting->default_payment_days }}">
            <small class="text-muted">
                Default payment due days for new suppliers.
            </small>
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#supplierSettingForm','{{ url('admin/setting/supplier') }}')">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>
