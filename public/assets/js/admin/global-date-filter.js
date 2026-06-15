var filterStartDate = '';
var filterEndDate = '';

function initGlobalDateFilter() {

    $('#date_filter').change(function () {

        let value = $(this).val();

        calculateDateRange(value);
    });

    $('#apply_custom_date').click(function () {

        filterStartDate = $('#start_date').val();
        filterEndDate = $('#end_date').val();

        $('#customDateModal').modal('hide');

        renderDateRange();
    });
}

function calculateDateRange(type) {

    let today = moment();

    switch (type) {

        case 'today':

            filterStartDate = today.format('YYYY-MM-DD');
            filterEndDate = today.format('YYYY-MM-DD');

            break;

        case 'yesterday':

            filterStartDate = today.clone().subtract(1, 'days').format('YYYY-MM-DD');
            filterEndDate = filterStartDate;

            break;

        case 'this_week':

            filterStartDate = today.clone().startOf('week').format('YYYY-MM-DD');
            filterEndDate = today.clone().endOf('week').format('YYYY-MM-DD');

            break;

        case 'last_week':

            filterStartDate = today.clone().subtract(1, 'week').startOf('week').format('YYYY-MM-DD');
            filterEndDate = today.clone().subtract(1, 'week').endOf('week').format('YYYY-MM-DD');

            break;

        case 'this_month':

            filterStartDate = today.clone().startOf('month').format('YYYY-MM-DD');
            filterEndDate = today.clone().endOf('month').format('YYYY-MM-DD');

            break;

        case 'last_month':

            filterStartDate = today.clone().subtract(1, 'month').startOf('month').format('YYYY-MM-DD');
            filterEndDate = today.clone().subtract(1, 'month').endOf('month').format('YYYY-MM-DD');

            break;

        case 'last_3_months':

            filterStartDate = today.clone().subtract(3, 'months').startOf('month').format('YYYY-MM-DD');
            filterEndDate = today.format('YYYY-MM-DD');

            break;

        case 'last_6_months':

            filterStartDate = today.clone().subtract(6, 'months').startOf('month').format('YYYY-MM-DD');
            filterEndDate = today.format('YYYY-MM-DD');

            break;

        case 'this_year':

            filterStartDate = today.clone().startOf('year').format('YYYY-MM-DD');
            filterEndDate = today.clone().endOf('year').format('YYYY-MM-DD');

            break;

        case 'previous_year':

            filterStartDate = today.clone().subtract(1, 'year').startOf('year').format('YYYY-MM-DD');
            filterEndDate = today.clone().subtract(1, 'year').endOf('year').format('YYYY-MM-DD');

            break;

        case 'this_financial_year':
            
            filterStartDate = CURRENT_YEAR + '-07-01';
            filterEndDate = (CURRENT_YEAR + 1) + '-06-30';

            break;

        case 'previous_financial_year':

            filterStartDate = (CURRENT_YEAR - 1) + '-07-01';
            filterEndDate = CURRENT_YEAR + '-06-30';

            break;

        case 'custom':

            $("#customDateModal").modal("show");
            return;
    }

    renderDateRange();
}

function renderDateRange() {

    $('#selected_date_range')
        .html(
            '<strong>Date Range:</strong> ' +
            filterStartDate +
            ' to ' +
            filterEndDate
        )
        .show();
}