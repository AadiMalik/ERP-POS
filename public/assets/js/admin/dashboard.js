/**
 * Business Dashboard filter bar - wires the shared global date-filter
 * (public/assets/js/admin/global-date-filter.js, already initialized by
 * layouts/js.blade.php before this file loads) into a plain GET page reload
 * of the dashboard's own filter form, per the filter design's "server-
 * rendered, single GET request per filter change" decision. Registered
 * after initGlobalDateFilter()'s own #date_filter/#apply_custom_date
 * handlers, so filterStartDate/filterEndDate are already up to date by the
 * time these run.
 */
$(function () {
    var $form = $('#dashboardFilterForm');

    if (!$form.length) {
        return;
    }

    function submitWithDates() {
        if (filterStartDate) {
            $('#dashboard_start_date').val(filterStartDate);
        }
        if (filterEndDate) {
            $('#dashboard_end_date').val(filterEndDate);
        }
        $form.trigger('submit');
    }

    $('#date_filter').on('change', function () {
        if ($(this).val() !== 'custom') {
            submitWithDates();
        }
    });

    $('#apply_custom_date').on('click', submitWithDates);

    $('#branch_id, #order_type_id, #order_source_id, #payment_method_id').on('change', function () {
        $form.trigger('submit');
    });
});
