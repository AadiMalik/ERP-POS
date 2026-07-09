<form id="customerSettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>Customer Setting</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <label>Customer Code Prefix</label>
            <input type="text" class="form-control" name="customer_code_prefix"
                value="{{ $customer_setting->customer_code_prefix }}">
        </div>

        <div class="col-md-6 mb-3">
            <label>Enable Credit Limit</label>
            <select class="form-select select2" name="customer_enable_credit_limit">
                <option value="1" {{ $customer_setting->enable_credit_limit == 1 ? 'selected' : '' }}>
                    Yes
                </option>
                <option value="0" {{ $customer_setting->enable_credit_limit == 0 ? 'selected' : '' }}>
                    No
                </option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>Default Credit Limit</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control" name="customer_credit_limit"
                value="{{ $customer_setting->credit_limit }}">
            <small class="text-muted">
                Default credit limit assigned to new customers.
            </small>
        </div>

        <div class="col-md-6 mb-3">
            <label>Enable Loyalty Program</label>
            <select class="form-select select2" name="loyalty_program">
                <option value="1" {{ $customer_setting->loyalty_program == 1 ? 'selected' : '' }}>
                    Yes
                </option>
                <option value="0" {{ $customer_setting->loyalty_program == 0 ? 'selected' : '' }}>
                    No
                </option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>Every Purchase Amount</label>
            <div class="input-group">
                <span class="input-group-text">Rs</span>
                <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                    name="loyalty_every_amount" value="{{ $customer_setting->loyalty_every_amount }}">
            </div>
            <small class="text-muted">
                Customer earns points for every entered purchase amount.
            </small>
        </div>

        <div class="col-md-6 mb-3">
            <label>Loyalty Points</label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control" name="loyalty_point_rate"
                value="{{ $customer_setting->loyalty_point_rate }}">
            <small class="text-muted">
                Points awarded for each purchase amount above.
            </small>
        </div>

        <div class="col-md-6 mb-3">
            <label>Minimum Order Amount</label>
            <div class="input-group">
                <span class="input-group-text">Rs</span>
                <input type="text" onkeypress="return isNumberKey(event)" class="form-control"
                    name="loyalty_min_order_amount" value="{{ $customer_setting->loyalty_min_order_amount }}">
            </div>
            <small class="text-muted">
                Minimum order amount required to earn loyalty points.
            </small>
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#customerSettingForm','{{ url('admin/setting/customer') }}')">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>
