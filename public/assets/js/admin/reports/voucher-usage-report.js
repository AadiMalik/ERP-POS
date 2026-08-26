function loadVoucherUsageSummary() {
    ajaxRequest({
        url: url_local + '/admin/reports/voucher-usage-report/summary',
        method: 'POST',
        data: getVoucherUsageFilters(),
    }).then(function (response) {
        var data = response.Data || {};
        $('#total_redemptions').text(data.total_redemptions ?? '-');
        $('#total_discount').text(typeof money === 'function' ? money(data.total_discount || 0) : (data.total_discount || 0));
        $('#unique_vouchers').text(data.unique_vouchers ?? '-');
        $('#unique_customers').text(data.unique_customers ?? '-');
    }).catch(function () {
        $('#total_redemptions, #total_discount, #unique_vouchers, #unique_customers').text('-');
    });
}

function getVoucherUsageFilters() {
    return {
        business_id: $('#business_id').val() || undefined,
        voucher_id: $('#voucher_id').val() || undefined,
        start_date: $('#start_date').val() || undefined,
        end_date: $('#end_date').val() || undefined,
    };
}

$(document).on('click', '#search_btn', function () {
    if (typeof initDataTablevoucher_usage_report_table === 'function') {
        initDataTablevoucher_usage_report_table();
    }
    loadVoucherUsageSummary();
});

$(document).on('click', '#reset_filter', function () {
    $('#business_id, #voucher_id').val('').trigger('change');
    $('#start_date, #end_date').val('');
    if (typeof initDataTablevoucher_usage_report_table === 'function') {
        initDataTablevoucher_usage_report_table();
    }
    loadVoucherUsageSummary();
});

$(function () {
    loadVoucherUsageSummary();
});
