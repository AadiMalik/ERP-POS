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
            <label class="d-block">Allow Mixed Sale Types</label>
            <input type="checkbox" class="form-check-input" name="allow_mixed_sale_types" value="1"
                {{ $pos_setting->allow_mixed_sale_types ? 'checked' : '' }}>
            <small class="text-muted d-block">Let a cashier set a different Sale Type per cart item instead of the whole order sharing one.</small>
        </div>

        <div class="col-md-3 mb-3">
            <label class="d-block">Allow Price Change in Cart</label>
            <input type="checkbox" class="form-check-input" name="allow_price_change_in_cart" value="1"
                {{ $pos_setting->allow_price_change_in_cart ? 'checked' : '' }}>
            <small class="text-muted d-block">When off, no cashier can edit a line's price in the cart. When on, the "Change Price" permission decides who can.</small>
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

@can('sale-type.view')
    <hr>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">Sale Types</h5>
            <small class="text-muted">Only the sale types you keep Active here appear on Product Variations and in POS.</small>
        </div>
        @can('sale-type.create')
            <button type="button" id="createNewSaleType" class="btn rounded-pill btn-primary">
                <i class="icon-base fa fa-plus mr-5"></i>Add Sale Type
            </button>
        @endcan
    </div>

    <div class="table-responsive">
        <table class="table" id="saleTypeTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Default</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="saleTypeTableBody"></tbody>
        </table>
    </div>
@endcan

<div class="modal fade" id="saleTypeModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="saleTypeModalHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="sale_type_form" name="sale_type_form">
                <div class="modal-body">
                    <input type="hidden" name="sale_type_id" id="sale_type_id">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sale_type_name" name="name" placeholder="e.g. Retail" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sale_type_code" name="code" placeholder="e.g. RETAIL" required>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sale_type_is_default" name="is_default" value="1">
                                <label class="form-check-label" for="sale_type_is_default">Set as Default</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select id="sale_type_status" name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
<script>
    // Row-level actions are rendered client-side (see renderSaleTypeRows()
    // below), so - unlike a Blade permission check on a static button - these
    // have to be read into JS explicitly. Server-side middleware on
    // SaleTypeController is still the real enforcement; this only keeps the
    // UI from offering actions a "User does not have the right permissions."
    // error would immediately reject.
    var canEditSaleType = @json(auth()->user()->can('sale-type.edit'));
    var canDeleteSaleType = @json(auth()->user()->can('sale-type.delete'));
    var canStatusSaleType = @json(auth()->user()->can('sale-type.status'));

    function renderSaleTypeRows(sale_types) {
        let rows = '';

        (sale_types || []).forEach(function (item) {
            let checked = item.status === 'active' ? 'checked' : '';

            let statusCell = canStatusSaleType
                ? `<div class="form-check form-switch mb-0">
                        <input class="form-check-input statusSaleType" type="checkbox" data-id="${item.sale_type_id}" ${checked}>
                    </div>`
                : `<span class="badge ${item.status === 'active' ? 'bg-label-success' : 'bg-label-secondary'}">${item.status === 'active' ? 'Active' : 'Inactive'}</span>`;

            let actionCell = '';
            if (canEditSaleType) {
                actionCell += `<a class="btn btn-icon btn-outline-primary mr-2" id="editSaleType" href="javascript:void(0)" data-id="${item.sale_type_id}">
                        <i class="fa fa-pencil"></i>
                    </a>`;
            }
            if (canDeleteSaleType) {
                actionCell += `<a class="btn btn-icon btn-outline-danger" id="deleteSaleType" data-id="${item.sale_type_id}">
                        <i class="fa fa-trash"></i>
                    </a>`;
            }
            if (!actionCell) {
                actionCell = '<span class="text-muted">-</span>';
            }

            rows += `<tr>
                <td>${item.name}</td>
                <td>${item.code}</td>
                <td>${item.is_default == 1 ? '<span class="badge bg-label-primary">Default</span>' : '-'}</td>
                <td>${statusCell}</td>
                <td>${actionCell}</td>
            </tr>`;
        });

        $('#saleTypeTableBody').html(rows || '<tr><td colspan="5" class="text-center">No sale types yet.</td></tr>');
    }

    function loadSaleTypes() {
        if (!$('#saleTypeTableBody').length) {
            return;
        }
        ajaxRequest({ url: url_local + '/admin/sale-type/list' }).then(function (response) {
            renderSaleTypeRows(response.Data);
        });
    }

    $(document).ready(function () {
        loadSaleTypes();
    });

    $('#createNewSaleType').click(function () {
        $('#sale_type_form')[0].reset();
        $('#sale_type_id').val('');
        $('#sale_type_status').val('active');
        $('#saveBtn').show();
        $('#saleTypeModalHeading').html('Add Sale Type');
        $('#saleTypeModal').modal('show');
    });

    editRecord({
        buttonClass: '#editSaleType',
        url: url_local + '/admin/sale-type',
        onSuccess: function (response) {
            let data = response.Data;
            $('#sale_type_id').val(data.sale_type_id);
            $('#sale_type_name').val(data.name);
            $('#sale_type_code').val(data.code);
            $('#sale_type_is_default').prop('checked', data.is_default == 1);
            $('#sale_type_status').val(data.status);
            $('#saleTypeModalHeading').html('Edit Sale Type');
            $('#saveBtn').show();
            $('#saleTypeModal').modal('show');
        }
    });

    saveRecord({
        formId: '#sale_type_form',
        url: url_local + '/admin/sale-type',
        modalId: '#saleTypeModal',
        tableCallback: function () {
            loadSaleTypes();
        },
        beforeSubmit: function () {
            if ($('#sale_type_name').val() == '') {
                errorMessage('Please Enter Name');
                return false;
            }
            if ($('#sale_type_code').val() == '') {
                errorMessage('Please Enter Code');
                return false;
            }
            return true;
        }
    });

    updateStatus({
        buttonClass: '.statusSaleType',
        url: url_local + '/admin/sale-type/change-status',
        tableCallback: function () {
            loadSaleTypes();
        }
    });

    deleteRecord({
        buttonClass: '#deleteSaleType',
        url: url_local + '/admin/sale-type',
        tableCallback: function () {
            loadSaleTypes();
        }
    });
</script>
@endpush
