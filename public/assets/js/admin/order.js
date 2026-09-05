$(document).ready(function () {
    $('#business_id').select2();
    $('#branch_id').select2();
    $('#warehouse_id').select2();
    $('#register_id').select2();
    $('#cashier_id').select2();
    $('#customer_id').select2();
    $('#order_type_id').select2();
    $('#order_source_id').select2();
    $('#payment_method_id').select2();
    $('#status').select2();
});

$('#search_btn').click(function () {
    initDataTableorder_table();
});

$('#reset_filter').click(function () {
    $('#order_id').val('');
    $('#daily_order_id').val('');
    $('#business_id').val('').trigger('change.select2');
    $('#branch_id').val('').trigger('change.select2');
    $('#warehouse_id').val('').trigger('change.select2');
    $('#register_id').val('').trigger('change.select2');
    $('#cashier_id').val('').trigger('change.select2');
    $('#customer_id').val('').trigger('change.select2');
    $('#order_type_id').val('').trigger('change.select2');
    $('#order_source_id').val('').trigger('change.select2');
    $('#payment_method_id').val('').trigger('change.select2');
    $('#status').val('').trigger('change.select2');
    $('#sale_date_start').val('');
    $('#sale_date_end').val('');
    $('#date_filter').val('').trigger('change');
    filterStartDate = '';
    filterEndDate = '';
    initDataTableorder_table();
});

// Superadmin only: branch/warehouse/register/cashier/customer/order-type/
// order-source/payment-method dropdowns start empty (scoped selects have no
// single business to resolve against) and are populated here once a
// business is chosen - mirrors the business->branch/warehouse cascade on the
// Purchase filter bar, bundled into one endpoint since Orders has far more
// dependent dropdowns.
$('#business_id').on('change', function () {
    let business_id = $(this).val();
    const i18n = window.i18n_orders || {};

    $('#branch_id').html('<option value="">' + (i18n.all_branches || '--All Branches--') + '</option>');
    $('#warehouse_id').html('<option value="">' + (i18n.all_warehouses || '--All Warehouses--') + '</option>');
    $('#register_id').html('<option value="">' + (i18n.all_registers || '--All Registers--') + '</option>');
    $('#cashier_id').html('<option value="">' + (i18n.all_cashiers || '--All Cashiers--') + '</option>');
    $('#customer_id').html('<option value="">' + (i18n.all_customers || '--All Customers--') + '</option>');
    $('#order_type_id').html('<option value="">' + (i18n.all_order_types || '--All Order Types--') + '</option>');
    $('#order_source_id').html('<option value="">' + (i18n.all_order_sources || '--All Order Sources--') + '</option>');
    $('#payment_method_id').html('<option value="">' + (i18n.all_payment_methods || '--All Payment Methods--') + '</option>');

    if (!business_id) {
        return;
    }

    ajaxRequest({
        url: url_local + '/admin/order/filter-options/' + business_id,
        data: {}
    }).then(function (response) {
        let data = response.Data;

        let branchOptions = '<option value="">' + (i18n.all_branches || '--All Branches--') + '</option>';
        $.each(data.branches, function (_, item) {
            branchOptions += `<option value="${item.branch_id}">${item.name}</option>`;
        });
        $('#branch_id').html(branchOptions);

        let warehouseOptions = '<option value="">' + (i18n.all_warehouses || '--All Warehouses--') + '</option>';
        $.each(data.warehouses, function (_, item) {
            warehouseOptions += `<option value="${item.warehouse_id}">${item.name}</option>`;
        });
        $('#warehouse_id').html(warehouseOptions);

        let registerOptions = '<option value="">' + (i18n.all_registers || '--All Registers--') + '</option>';
        $.each(data.registers, function (_, item) {
            registerOptions += `<option value="${item.pos_register_id}">${item.name}</option>`;
        });
        $('#register_id').html(registerOptions);

        let cashierOptions = '<option value="">' + (i18n.all_cashiers || '--All Cashiers--') + '</option>';
        $.each(data.cashiers, function (_, item) {
            cashierOptions += `<option value="${item.id}">${item.name}</option>`;
        });
        $('#cashier_id').html(cashierOptions);

        let customerOptions = '<option value="">' + (i18n.all_customers || '--All Customers--') + '</option>';
        $.each(data.customers, function (_, item) {
            customerOptions += `<option value="${item.customer_id}">${item.name}</option>`;
        });
        $('#customer_id').html(customerOptions);

        let orderTypeOptions = '<option value="">' + (i18n.all_order_types || '--All Order Types--') + '</option>';
        $.each(data.order_types, function (_, item) {
            orderTypeOptions += `<option value="${item.order_type_id}">${item.name}</option>`;
        });
        $('#order_type_id').html(orderTypeOptions);

        let orderSourceOptions = '<option value="">' + (i18n.all_order_sources || '--All Order Sources--') + '</option>';
        $.each(data.order_sources, function (_, item) {
            orderSourceOptions += `<option value="${item.order_source_id}">${item.name}</option>`;
        });
        $('#order_source_id').html(orderSourceOptions);

        let paymentMethodOptions = '<option value="">' + (i18n.all_payment_methods || '--All Payment Methods--') + '</option>';
        $.each(data.payment_methods, function (_, item) {
            paymentMethodOptions += `<option value="${item.payment_method_id}">${item.name}</option>`;
        });
        $('#payment_method_id').html(paymentMethodOptions);
    }).catch(function (err) {
        errorMessage(err.Message ?? (window.i18n_orders?.something_went_wrong || 'Something went wrong.'));
    });
});

$(document).on('click', '.cancel-order-btn', function () {
    $('#cancel_order_id').val($(this).data('id'));
    $('#cancel_order_reason').val('');
    $('#cancelOrderModal').modal('show');
});

$('#confirmCancelOrder').click(function () {
    let order_id = $('#cancel_order_id').val();
    let reason = $('#cancel_order_reason').val();

    if (!reason || !reason.trim()) {
        errorMessage(window.i18n_orders?.cancellation_reason_required || 'A cancellation reason is required.');
        return;
    }

    ajaxRequest({
        url: url_local + '/admin/order/cancel',
        method: 'POST',
        data: { order_id: order_id, reason: reason },
    }).then(function (response) {
        successMessage(response.Message);
        $('#cancelOrderModal').modal('hide');
        initDataTableorder_table();
    }).catch(function (err) {
        errorMessage(err.Message || window.i18n_orders?.unable_cancel || 'Unable to cancel order.');
    });
});
