
function tPos(key, fallback) {
    return (window.i18n_pos && window.i18n_pos[key]) || fallback;
}

function money(v) {
    v = parseFloat(v || 0);
    if (isNaN(v)) v = 0;
    return v.toFixed(2);
}

function escapeHtml(str) {
    return $('<div>').text(str == null ? '' : str).html();
}

function currentFilterParams() {
    return {
        daily_order_id: $('#daily_order_id').val(),
        sale_date_start: $('#sale_date_start').val(),
        sale_date_end: $('#sale_date_end').val(),
        customer_id: $('#customer_id').val(),
        order_type_id: $('#order_type_id').val(),
        status: $('#status').val(),
        payment_status: $('#payment_status').val(),
        payment_method_id: $('#payment_method_id').val(),
        cashier_id: $('#cashier_id').val(),
        branch_id: $('#branch_id').val()
    };
}

$(document).ready(function () {
    $('#customer_id').select2();
    $('#order_type_id').select2();
    $('#status').select2();
    $('#payment_status').select2();
    $('#payment_method_id').select2();
    $('#cashier_id').select2();
    $('#branch_id').select2();
});

$('#search_btn').click(function () {
    initDataTableorder_history_table();

    if ($('#summarySection').is(':visible')) {
        loadHistorySummary();
    }
});

$('#reset_filter').click(function () {
    $('#daily_order_id').val('');
    $('#customer_id').val('').trigger('change.select2');
    $('#order_type_id').val('').trigger('change.select2');
    $('#status').val('').trigger('change.select2');
    $('#payment_status').val('').trigger('change.select2');
    $('#payment_method_id').val('').trigger('change.select2');
    $('#cashier_id').val('').trigger('change.select2');
    $('#branch_id').val('').trigger('change.select2');

    // Order Takers' date range is locked to today - the inputs are marked
    // readonly server-side (see admin/pos/order-history/index.blade.php),
    // so Reset must not clear them; every other role gets a full reset.
    if (!IS_ORDER_TAKER) {
        $('#sale_date_start').val('');
        $('#sale_date_end').val('');
    }

    initDataTableorder_history_table();

    if ($('#summarySection').is(':visible')) {
        loadHistorySummary();
    }
});

/* =========================================================
   Order Detail modal - the POS Order History "View" action
   opens this in-page instead of navigating to the Admin
   Panel's order.show page. Header/list fields already
   resolved by the datatable row are reused as-is; items and
   payments are fetched from the existing order/details/{id}
   endpoint (the same one the POS cart-resume flow uses).
   ========================================================= */
$('#order_history_table').on('click', 'a[title="View"]', function (e) {
    e.preventDefault();

    var tr = $(this).closest('tr');
    var rowData = order_history_table.row(tr).data();
    if (!rowData) {
        return;
    }

    openOrderDetailModal(rowData);
});

function openOrderDetailModal(rowData) {
    $('#odOrderNo').text(rowData.daily_order_id || '');
    $('#odOrderDate').text(rowData.order_date || '-');
    $('#odOrderType').text(rowData.order_type || '-');
    $('#odOrderSource').text(rowData.order_source || '-');
    $('#odCashier').text(rowData.cashier || '-');
    $('#odCustomer').text(rowData.customer || '-');
    $('#odStatus').html(rowData.status || '-');
    $('#odPaymentStatus').html(rowData.payment_status || '-');
    $('#odPaymentMethod').text(rowData.payment_method || '-');

    $('#odThermalPrintBtn').attr('href', ORDER_HISTORY_URLS.print + '/' + rowData.order_id + '/thermal-print');
    $('#odReorderBtn').attr('href', ORDER_HISTORY_URLS.pos_screen + '?reorder_from=' + rowData.order_id);
    $('#odCorrectBtn').addClass('d-none').attr('href', '#');

    $('#odItemsBody').html('<tr><td colspan="6" class="text-center text-muted">Loading...</td></tr>');
    $('#odPaymentsBody').html('<tr><td colspan="3" class="text-center text-muted">Loading...</td></tr>');
    $('#odSubtotal, #odDiscount, #odTax, #odTotal, #odPaid, #odDue').text('-');

    var modalEl = document.getElementById('orderDetailModal');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    ajaxRequest({ url: ORDER_HISTORY_URLS.details + '/' + rowData.order_id })
        .then(function (response) {
            renderOrderDetail(response.Data || {});
        })
        .catch(function (err) {
            $('#odItemsBody').html('<tr><td colspan="6" class="text-center text-danger">' + escapeHtml(err.Message || tPos('unable_load_order_details', 'Unable to load order details.')) + '</td></tr>');
            $('#odPaymentsBody').html('');
        });
}

var currentOrderDetail = { order_id: null, due_amount: 0 };

function renderOrderDetail(data) {
    var header = data.header || {};
    var details = data.details || [];
    var payments = data.payments || [];
    var customerPayments = data.customer_payments || [];

    var $items = $('#odItemsBody').empty();
    if (!details.length) {
        $items.append('<tr><td colspan="6" class="text-center text-muted">No items</td></tr>');
    } else {
        details.forEach(function (item) {
            $items.append(
                '<tr>' +
                    '<td>' + escapeHtml(item.product_name) + '</td>' +
                    '<td>' + escapeHtml(item.product_variation_name) + '</td>' +
                    '<td class="text-end">' + escapeHtml(item.quantity) + '</td>' +
                    '<td>' + escapeHtml(item.unit_name) + '</td>' +
                    '<td class="text-end">' + money(item.unit_price) + '</td>' +
                    '<td class="text-end">' + money(item.total) + '</td>' +
                '</tr>'
            );
        });
    }

    var $payments = $('#odPaymentsBody').empty();
    if (!payments.length) {
        $payments.append('<tr><td colspan="3" class="text-center text-muted">No payments recorded</td></tr>');
    } else {
        payments.forEach(function (payment) {
            $payments.append(
                '<tr>' +
                    '<td>' + escapeHtml(payment.payment_method_name) + '</td>' +
                    '<td>' + escapeHtml(payment.reference_no || '-') + '</td>' +
                    '<td class="text-end">' + money(payment.amount) + '</td>' +
                '</tr>'
            );
        });
    }

    var $customerPayments = $('#odCustomerPaymentsBody').empty();
    if (!customerPayments.length) {
        $customerPayments.append('<tr><td colspan="5" class="text-center text-muted">No payments received yet</td></tr>');
    } else {
        customerPayments.forEach(function (payment) {
            $customerPayments.append(
                '<tr>' +
                    '<td>' + escapeHtml(payment.payment_date || '-') + '</td>' +
                    '<td>' + escapeHtml(payment.payment_method) + '</td>' +
                    '<td>' + escapeHtml(payment.status) + '</td>' +
                    '<td class="text-end">' + money(payment.amount) + '</td>' +
                    '<td class="text-end">' + money(payment.remaining_due) + '</td>' +
                '</tr>'
            );
        });
    }

    var due = header.due_amount !== undefined
        ? (parseFloat(header.due_amount) || 0)
        : Math.max((parseFloat(header.total) || 0) - (parseFloat(header.paid_amount) || 0), 0);

    $('#odSubtotal').text(money(header.subtotal));
    $('#odDiscount').text(money(header.discount_amount));
    $('#odTax').text(money(header.tax_amount));
    $('#odTotal').text(money(header.total));
    $('#odPaid').text(money(header.paid_amount));
    $('#odDue').text(money(due));

    currentOrderDetail = { order_id: header.order_id, due_amount: due };
    $('#odReceivePaymentBtn').toggleClass('d-none', !(CAN_RECEIVE_PAYMENT && due > 0));

    if (typeof CAN_CORRECT_ORDER !== 'undefined' && CAN_CORRECT_ORDER && header.can_correct) {
        $('#odCorrectBtn')
            .removeClass('d-none')
            .attr('href', ORDER_HISTORY_URLS.pos_screen + '?correct=' + header.order_id);
    } else {
        $('#odCorrectBtn').addClass('d-none').attr('href', '#');
    }
}

/* =========================================================
   Receive Payment - quick capture against the currently open
   order's remaining due, via admin/customer-payment/receive
   (create + auto-post in one call). Mirrors the same due-amount
   cap already enforced server-side in CustomerPaymentService::save().
   ========================================================= */
$('#odReceivePaymentBtn').click(function () {
    if (!currentOrderDetail.order_id) {
        return;
    }

    $('#rpOrderNo').text($('#odOrderNo').text());
    $('#rpDueAmount').text(money(currentOrderDetail.due_amount));
    $('#rpAmount').val(money(currentOrderDetail.due_amount));
    $('#rpPaymentMethod').val('cash');
    $('#rpReferenceNo').val('');

    var modalEl = document.getElementById('receivePaymentModal');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
});

$('#rpSubmitBtn').click(function () {
    // Parse after decimal() formatting - decimal() returns a string via
    // toFixed(), and string ">" compares lexicographically (e.g. "9.00" >
    // "10.61"), which wrongly blocked valid partial payments.
    var amount = parseFloat(decimal($('#rpAmount').val() || 0));
    var due = parseFloat(decimal(currentOrderDetail.due_amount || 0));

    if (!currentOrderDetail.order_id) {
        return;
    }
    if (amount <= 0) {
        errorMessage(tPos('payment_amount_gt_zero', 'Payment amount must be greater than zero.'));
        return;
    }
    if (amount > due) {
        errorMessage(tPos('payment_exceeds_due', 'Payment amount exceeds the order\'s remaining due.'));
        return;
    }

    var $btn = $(this).prop('disabled', true);

    ajaxRequest({
        url: ORDER_HISTORY_URLS.receive_payment,
        method: 'POST',
        data: {
            order_id: currentOrderDetail.order_id,
            amount: amount,
            payment_method: $('#rpPaymentMethod').val(),
            reference_no: $('#rpReferenceNo').val()
        }
    }).then(function (response) {
        successMessage(response.Message || tPos('payment_received', 'Payment received.'));
        bootstrap.Modal.getInstance(document.getElementById('receivePaymentModal')).hide();
        renderOrderDetail(response.Data || {});
        if (window.order_history_table) {
            order_history_table.ajax.reload(null, false);
        }
    }).catch(function (err) {
        errorMessage(err.Message || tPos('unable_receive_payment', 'Unable to receive payment.'));
    }).then(function () {
        $btn.prop('disabled', false);
    });
});

/* =========================================================
   Sales Summary panel - aggregate totals for the currently
   applied filters, with a thermal-print option. Backed by
   order/history-summary (data) and order/history-summary/print
   (thermal view), both scoped/filtered identically to the
   table above.
   ========================================================= */
$('#toggleSummary').click(function () {
    var $section = $('#summarySection');
    $section.toggle();

    if ($section.is(':visible')) {
        loadHistorySummary();
    }
});

$('#printSummaryBtn').click(function () {
    var query = $.param(currentFilterParams());
    window.open(ORDER_HISTORY_URLS.summary_print + '?' + query, '_blank');
});

function loadHistorySummary() {
    $('#summarySection').css('opacity', 0.6);

    ajaxRequest({ url: ORDER_HISTORY_URLS.summary, method: 'POST', data: currentFilterParams() })
        .then(function (response) {
            var s = response.Data || {};
            $('#sumTotalOrders').text(s.total_orders || 0);
            $('#sumTotalSales').text(money(s.total_sales));
            $('#sumTotalPaid').text(money(s.total_paid));
            $('#sumTotalDue').text(money(s.total_due));

            var $status = $('#sumByStatus').empty();
            var by_status = s.by_status || {};
            if (!Object.keys(by_status).length) {
                $status.append('<span class="text-muted small">No data</span>');
            } else {
                $.each(by_status, function (key, count) {
                    $status.append('<span class="badge bg-label-secondary">' + escapeHtml(key) + ': ' + count + '</span>');
                });
            }

            var $method = $('#sumByPaymentMethod').empty();
            var by_method = s.by_payment_method || {};
            if (!Object.keys(by_method).length) {
                $method.append('<span class="text-muted small">No data</span>');
            } else {
                $.each(by_method, function (key, amount) {
                    $method.append('<span class="badge bg-label-info">' + escapeHtml(key) + ': ' + money(amount) + '</span>');
                });
            }
        })
        .catch(function (err) {
            errorMessage(err.Message || tPos('unable_load_sales_summary', 'Unable to load sales summary.'));
        })
        .then(function () {
            $('#summarySection').css('opacity', 1);
        });
}
