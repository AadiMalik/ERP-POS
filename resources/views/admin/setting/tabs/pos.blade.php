<form id="posSettingForm">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <h4>{{ __('settings.pos_title') }}</h4>
            <hr>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.pos_register_mode') }}</label>
            <select class="form-select select2" name="register_mode">
                <option value="manual" {{ $pos_setting->register_mode == 'manual' ? 'selected' : '' }}>{{ __('common.manual') }}</option>
                <option value="automatic" {{ $pos_setting->register_mode == 'automatic' ? 'selected' : '' }}>{{ __('common.automatic') }}</option>
            </select>
        </div>

        <div class="col-md-3 mb-3 register-mode-automatic-field">
            <label>{{ __('settings.pos_default_open_time') }}</label>
            <input type="time" class="form-control" name="open_time" value="{{ $pos_setting->open_time }}">
            <small class="text-muted">{{ __('settings.pos_default_open_time_help') }}</small>
        </div>

        <div class="col-md-3 mb-3 register-mode-automatic-field">
            <label>{{ __('settings.pos_default_close_time') }}</label>
            <input type="time" class="form-control" name="close_time" value="{{ $pos_setting->close_time }}">
            <small class="text-muted">{{ __('settings.pos_default_close_time_help') }}</small>
        </div>

        <div class="col-md-6 mb-3">
            <label>{{ __('settings.pos_default_walk_in_customer') }}</label>
            <select class="form-select select2" name="default_customer_user_id">
                <option value="">{{ __('settings.pos_select_customer') }}</option>
                @foreach ($pos_customers as $customer)
                    <option value="{{ $customer->user_id }}"
                        {{ $pos_setting->default_customer_user_id == $customer->user_id ? 'selected' : '' }}>
                        {{ $customer->code }} - {{ $customer->user->name ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4 mb-3">
            <label>{{ __('settings.pos_invoice_prefix') }}</label>
            <input type="text" class="form-control" name="invoice_prefix" value="{{ $pos_setting->invoice_prefix }}">
        </div>

        <div class="col-md-4 mb-3">
            <label>{{ __('settings.pos_invoice_start_number') }}</label>
            <input type="number" class="form-control" name="invoice_start" value="{{ $pos_setting->invoice_start }}">
        </div>

        <div class="col-md-4 mb-3">
            <label>{{ __('settings.pos_daily_order_id_reset') }}</label>
            <select class="form-select select2" name="daily_order_id_reset">
                <option value="daily" {{ $pos_setting->daily_order_id_reset == 'daily' ? 'selected' : '' }}>{{ __('settings.pos_reset_daily') }}</option>
                <option value="never" {{ $pos_setting->daily_order_id_reset == 'never' ? 'selected' : '' }}>{{ __('settings.pos_reset_never') }}</option>
            </select>
        </div>

        <div class="col-md-12 mb-3">
            <label>{{ __('settings.pos_invoice_footer') }}</label>
            <textarea class="form-control" name="invoice_footer" rows="2">{{ $pos_setting->invoice_footer }}</textarea>
        </div>

        <div class="col-md-3 mb-3">
            <label class="d-block">{{ __('settings.pos_enable_discount') }}</label>
            <input type="checkbox" class="form-check-input" name="enable_discount" value="1"
                {{ $pos_setting->enable_discount ? 'checked' : '' }}>
        </div>

        <div class="col-md-3 mb-3">
            <label>{{ __('settings.pos_discount_level') }}</label>
            <select class="form-select select2" name="discount_level">
                <option value="line" {{ $pos_setting->discount_level == 'line' ? 'selected' : '' }}>{{ __('settings.pos_discount_line_only') }}</option>
                <option value="order" {{ $pos_setting->discount_level == 'order' ? 'selected' : '' }}>{{ __('settings.pos_discount_order_only') }}</option>
                <option value="both" {{ $pos_setting->discount_level == 'both' ? 'selected' : '' }}>{{ __('settings.pos_discount_both') }}</option>
            </select>
        </div>

        <div class="col-md-3 mb-3">
            <label class="d-block">{{ __('settings.pos_enable_hold_order') }}</label>
            <input type="checkbox" class="form-check-input" name="enable_hold_order" value="1"
                {{ $pos_setting->enable_hold_order ? 'checked' : '' }}>
        </div>

        <div class="col-md-3 mb-3">
            <label class="d-block">{{ __('settings.pos_allow_mixed_sale_types') }}</label>
            <input type="checkbox" class="form-check-input" name="allow_mixed_sale_types" value="1"
                {{ $pos_setting->allow_mixed_sale_types ? 'checked' : '' }}>
            <small class="text-muted d-block">{{ __('settings.pos_allow_mixed_sale_types_help') }}</small>
        </div>

        <div class="col-md-3 mb-3">
            <label class="d-block">{{ __('settings.pos_allow_price_change_in_cart') }}</label>
            <input type="checkbox" class="form-check-input" name="allow_price_change_in_cart" value="1"
                {{ $pos_setting->allow_price_change_in_cart ? 'checked' : '' }}>
            <small class="text-muted d-block">{{ __('settings.pos_allow_price_change_in_cart_help') }}</small>
        </div>

        <div class="col-md-3 mb-3">
            <label class="d-block">{{ __('settings.pos_allow_price_below_minimum') }}</label>
            <input type="checkbox" class="form-check-input" name="allow_price_below_minimum" value="1"
                {{ $pos_setting->allow_price_below_minimum ? 'checked' : '' }}>
            <small class="text-muted d-block">{{ __('settings.pos_allow_price_below_minimum_help') }}</small>
        </div>

        <div class="col-md-3 mb-3">
            <label class="d-block">{{ __('settings.pos_auto_print_invoice') }}</label>
            <input type="checkbox" class="form-check-input" name="auto_print_invoice" value="1"
                {{ $pos_setting->auto_print_invoice ? 'checked' : '' }}>
            <small class="text-muted d-block">{{ __('settings.pos_auto_print_invoice_help') }}</small>
        </div>

        <div class="col-md-3 mb-3">
            <label class="d-block">{{ __('settings.pos_show_product_image') }}</label>
            <input type="checkbox" class="form-check-input" name="show_product_image" value="1"
                {{ $pos_setting->show_product_image ? 'checked' : '' }}>
        </div>

        <div class="col-md-3 mb-3">
            <label class="d-block">{{ __('settings.pos_allow_backdated_sale') }}</label>
            <input type="checkbox" class="form-check-input pos-config-field" name="allow_backdated_sale" value="1"
                {{ $pos_setting->allow_backdated_sale ? 'checked' : '' }}>
        </div>

        <div class="col-md-3 mb-3">
            <label>{{ __('settings.pos_backdated_sale_max_days') }}</label>
            <input type="number" class="form-control" name="backdated_sale_max_days" min="0"
                value="{{ $pos_setting->backdated_sale_max_days }}">
        </div>

        <div class="col-md-12">
            <hr>
            <h5>{{ __('settings.pos_return_refund_heading') }}</h5>
        </div>

        <div class="col-md-4 mb-3">
            <label>{{ __('settings.pos_return_window_days') }}</label>
            <input type="number" class="form-control" name="return_window_days" min="0"
                value="{{ $pos_setting->return_window_days }}">
        </div>

        <div class="col-md-4 mb-3">
            <label class="d-block">{{ __('settings.pos_require_return_reason') }}</label>
            <input type="checkbox" class="form-check-input" name="require_return_reason" value="1"
                {{ $pos_setting->require_return_reason ? 'checked' : '' }}>
        </div>

        <div class="col-md-4 mb-3">
            <label class="d-block">{{ __('settings.pos_allow_partial_return') }}</label>
            <input type="checkbox" class="form-check-input" name="allow_partial_return" value="1"
                {{ $pos_setting->allow_partial_return ? 'checked' : '' }}>
        </div>

        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button"
                    class="btn btn-primary"
                    onclick="saveSetting('#posSettingForm','{{ url('admin/setting/pos') }}')">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</form>

@can('sale-type.view')
    <hr>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">{{ __('settings.pos_sale_types_heading') }}</h5>
            <small class="text-muted">{{ __('settings.pos_sale_types_help') }}</small>
        </div>
        @can('sale-type.create')
            <button type="button" id="createNewSaleType" class="btn rounded-pill btn-primary">
                <i class="icon-base fa fa-plus mr-5"></i>{{ __('settings.pos_add_sale_type') }}
            </button>
        @endcan
    </div>

    <div class="table-responsive">
        <table class="table" id="saleTypeTable">
            <thead>
                <tr>
                    <th>{{ __('common.name') }}</th>
                    <th>{{ __('common.code') }}</th>
                    <th>{{ __('common.default') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th>{{ __('common.action') }}</th>
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
                            <label class="form-label">{{ __('common.name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sale_type_name" name="name" placeholder="{{ __('settings.pos_name_placeholder') }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('common.code') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sale_type_code" name="code" placeholder="{{ __('settings.pos_code_placeholder') }}" required>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sale_type_is_default" name="is_default" value="1">
                                <label class="form-check-label" for="sale_type_is_default">{{ __('settings.pos_set_as_default') }}</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select id="sale_type_status" name="status" class="form-select">
                                <option value="active">{{ __('common.active') }}</option>
                                <option value="inactive">{{ __('common.inactive') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">{{ __('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
<script>
    window.i18n_settings = Object.assign(window.i18n_settings || {}, {
        pos_add_sale_type: @json(__('settings.pos_add_sale_type')),
        pos_edit_sale_type: @json(__('settings.pos_edit_sale_type')),
        pos_no_sale_types: @json(__('settings.pos_no_sale_types')),
        pos_default_badge: @json(__('common.default')),
        active: @json(__('common.active')),
        inactive: @json(__('common.inactive')),
        please_enter_name: @json(__('common.please_enter_name')),
        please_enter_code: @json(__('common.please_enter_code')),
    });

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
        let i18n = window.i18n_settings || {};

        (sale_types || []).forEach(function (item) {
            let checked = item.status === 'active' ? 'checked' : '';

            let statusCell = canStatusSaleType
                ? `<div class="form-check form-switch mb-0">
                        <input class="form-check-input statusSaleType" type="checkbox" data-id="${item.sale_type_id}" ${checked}>
                    </div>`
                : `<span class="badge ${item.status === 'active' ? 'bg-label-success' : 'bg-label-secondary'}">${item.status === 'active' ? (i18n.active || 'Active') : (i18n.inactive || 'Inactive')}</span>`;

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
                <td>${item.is_default == 1 ? '<span class="badge bg-label-primary">' + (i18n.pos_default_badge || 'Default') + '</span>' : '-'}</td>
                <td>${statusCell}</td>
                <td>${actionCell}</td>
            </tr>`;
        });

        $('#saleTypeTableBody').html(rows || '<tr><td colspan="5" class="text-center">' + (i18n.pos_no_sale_types || 'No sale types yet.') + '</td></tr>');
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
        $('#saleTypeModalHeading').html((window.i18n_settings && window.i18n_settings.pos_add_sale_type) || 'Add Sale Type');
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
            $('#saleTypeModalHeading').html((window.i18n_settings && window.i18n_settings.pos_edit_sale_type) || 'Edit Sale Type');
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
                errorMessage((window.i18n_settings && window.i18n_settings.please_enter_name) || 'Please Enter Name');
                return false;
            }
            if ($('#sale_type_code').val() == '') {
                errorMessage((window.i18n_settings && window.i18n_settings.please_enter_code) || 'Please Enter Code');
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
