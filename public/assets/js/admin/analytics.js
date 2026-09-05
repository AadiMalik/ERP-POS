/**
 * Advanced Analytics & BI dashboard — AJAX widget loader + ApexCharts.
 * Filter form still does a GET reload (same as Home Dashboard); widgets
 * fetch via analytics.data / analytics.table after paint.
 */
(function ($) {
    'use strict';

    var cfg = window.AnalyticsConfig || {};
    var charts = {};
    var chartColors = [
        (window.config && config.colors.primary) || '#696cff',
        (window.config && config.colors.success) || '#71dd37',
        (window.config && config.colors.info) || '#03c3ec',
        (window.config && config.colors.warning) || '#ffab00',
        (window.config && config.colors.danger) || '#ff3e1d',
        (window.config && config.colors.secondary) || '#8592a3'
    ];

    function qs() {
        var params = new URLSearchParams(window.location.search);
        return params.toString();
    }

    function money(v) {
        var n = Number(v || 0);
        var sym = cfg.currencySymbol || '';
        return sym + ' ' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function destroyChart(key) {
        if (charts[key]) {
            try { charts[key].destroy(); } catch (e) {}
            charts[key] = null;
        }
    }

    function fetchWidget(widget) {
        return $.getJSON(cfg.dataUrl + '/' + widget + '?' + qs());
    }

    function fetchTable(widget) {
        return $.ajax({
            url: cfg.tableUrl + '/' + widget + '?' + qs(),
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            }
        });
    }

    function deltaBadge(deltas, key) {
        if (!deltas || typeof deltas[key] === 'undefined') {
            return '';
        }
        var d = Number(deltas[key]);
        var cls = d >= 0 ? 'bg-label-success' : 'bg-label-danger';
        var arrow = d >= 0 ? '↑' : '↓';
        return '<span class="badge ' + cls + ' ms-1" title="' + (cfg.labels.deltaVsPrevious || '') + '">' +
            arrow + ' ' + Math.abs(d).toFixed(1) + '%</span>';
    }

    function kpiCard(label, value, icon, color, extraHtml, estimated) {
        var est = estimated
            ? '<span class="badge bg-label-warning ms-1">' + (cfg.labels.estimated || 'Estimated') + '</span>'
            : '';
        return (
            '<div class="col-sm-6 col-xl-3 mb-4">' +
            '<div class="card h-100 erp-kpi-card erp-kpi-card--gradient" style="--erp-kpi-color: var(--bs-' + color + '); --erp-kpi-color-rgb: var(--bs-' + color + '-rgb);">' +
            '<div class="card-body"><div class="d-flex justify-content-between align-items-start gap-2"><div>' +
            '<span class="erp-kpi-label text-muted">' + label + est + '</span>' +
            '<h4 class="erp-kpi-value mb-0 text-body">' + value + (extraHtml || '') + '</h4>' +
            '</div><div class="erp-kpi-icon"><i class="fa ' + icon + '"></i></div></div></div></div></div>'
        );
    }

    function renderKpis(sales, purchases, inventory, finance, marginRows) {
        var $row = $('#analyticsKpiRow');
        $('#analyticsKpiLoading').remove();
        var current = sales.current || sales;
        var deltas = sales.deltas || {};
        var html = '';

        html += kpiCard('Total Sales', money(current.total_sales), 'fa-cash-register', 'primary', deltaBadge(deltas, 'total_sales'));
        html += kpiCard('Net Sales', money(current.net_sales), 'fa-chart-line', 'info', deltaBadge(deltas, 'net_sales'));
        html += kpiCard('Total Orders', Number(current.total_orders || 0).toLocaleString(), 'fa-receipt', 'secondary', deltaBadge(deltas, 'total_orders'));
        html += kpiCard('Avg Order Value', money(current.average_order_value), 'fa-tags', 'warning', deltaBadge(deltas, 'average_order_value'));

        if (purchases) {
            var pc = purchases.current || purchases;
            var pd = purchases.deltas || {};
            html += kpiCard('Purchases', money(pc.total_purchase_amount || pc.total_purchases || 0), 'fa-truck-loading', 'warning', deltaBadge(pd, 'total_purchase_amount'));
        }

        if (inventory) {
            html += kpiCard('Stock Value', money(inventory.stock_value), 'fa-warehouse', 'success');
            html += kpiCard('Low Stock', Number(inventory.low_stock_count || 0).toLocaleString(), 'fa-exclamation-triangle', 'danger');
        }

        if (finance && Object.keys(finance).length) {
            html += kpiCard('Net Profit', money(finance.net_profit), 'fa-sack-dollar', (finance.net_profit || 0) >= 0 ? 'success' : 'danger');
            html += kpiCard('Gross Profit', money(finance.gross_profit), 'fa-chart-line', (finance.gross_profit || 0) >= 0 ? 'success' : 'danger');
            html += kpiCard('Expenses', money(finance.total_expenses), 'fa-file-invoice-dollar', 'danger');
        }

        if (marginRows && marginRows.length) {
            var totalMargin = marginRows.reduce(function (sum, r) { return sum + Number(r.estimated_margin || 0); }, 0);
            html += kpiCard('Est. Product Margin', money(totalMargin), 'fa-percentage', 'primary', '', true);
        }

        $row.html(html);
    }

    function showEmpty(chartId, emptyId, empty) {
        if (empty) {
            $('#' + chartId).addClass('d-none');
            $('#' + emptyId).removeClass('d-none');
        } else {
            $('#' + chartId).removeClass('d-none');
            $('#' + emptyId).addClass('d-none');
        }
    }

    function renderTrend(sales) {
        destroyChart('salesTrend');
        var trend = sales.daily_trend || {};
        var keys = Object.keys(trend);
        showEmpty('analyticsSalesTrendChart', 'analyticsSalesTrendEmpty', !keys.length);
        if (!keys.length || typeof ApexCharts === 'undefined') {
            return;
        }

        charts.salesTrend = new ApexCharts(document.querySelector('#analyticsSalesTrendChart'), {
            series: [{ name: cfg.labels.salesTrend || 'Sales', data: keys.map(function (k) { return Number(trend[k]); }) }],
            chart: { type: 'area', height: 300, toolbar: { show: false } },
            colors: [chartColors[0]],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
            xaxis: { categories: keys },
            tooltip: { y: { formatter: function (val) { return money(val); } } }
        });
        charts.salesTrend.render();
    }

    function renderBar(key, el, emptyId, labels, values, name) {
        destroyChart(key);
        showEmpty(el.replace('#', ''), emptyId, !labels.length);
        if (!labels.length || typeof ApexCharts === 'undefined') {
            return;
        }
        charts[key] = new ApexCharts(document.querySelector(el), {
            series: [{ name: name || 'Value', data: values }],
            chart: { type: 'bar', height: 280, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
            colors: [chartColors[0]],
            dataLabels: { enabled: false },
            xaxis: { categories: labels },
            tooltip: { y: { formatter: function (val) { return money(val); } } }
        });
        charts[key].render();
    }

    function renderDonut(key, el, emptyId, labels, values) {
        destroyChart(key);
        showEmpty(el.replace('#', ''), emptyId, !labels.length);
        if (!labels.length || typeof ApexCharts === 'undefined') {
            return;
        }
        charts[key] = new ApexCharts(document.querySelector(el), {
            series: values,
            labels: labels,
            chart: { type: 'donut', height: 280 },
            colors: chartColors,
            legend: { position: 'bottom' },
            dataLabels: { enabled: true, formatter: function (val) { return val.toFixed(1) + '%'; } },
            tooltip: { y: { formatter: function (val) { return money(val); } } }
        });
        charts[key].render();
    }

    function renderSegments(data) {
        var labels = [
            cfg.labels.segmentNew || 'New',
            cfg.labels.segmentReturning || 'Returning',
            cfg.labels.segmentWalkin || 'Walk-in'
        ];
        var values = [
            Number((data.new && data.new.revenue) || 0),
            Number((data.returning && data.returning.revenue) || 0),
            Number((data.walkin && data.walkin.revenue) || 0)
        ];
        var has = values.some(function (v) { return v > 0; });
        if (!has) {
            values = [
                Number((data.new && data.new.order_count) || 0),
                Number((data.returning && data.returning.order_count) || 0),
                Number((data.walkin && data.walkin.order_count) || 0)
            ];
            has = values.some(function (v) { return v > 0; });
        }
        renderDonut('segments', '#analyticsSegmentsChart', 'analyticsSegmentsEmpty', has ? labels : [], has ? values : []);
    }

    function rowName(r) {
        return r.product_name || r.customer || r.branch_name || r.branch || r.order_source_name || r.payment_method_name || r.name || '';
    }

    function rowNet(r) {
        return Number(r.net || r.total || r.total_revenue || r.estimated_margin || 0);
    }

    function fillMarginTable(rows) {
        var $tb = $('#analyticsMarginTable tbody').empty();
        if (!rows.length) {
            $tb.append('<tr><td colspan="6" class="text-muted">' + (cfg.labels.noData || '') + '</td></tr>');
            return;
        }
        rows.forEach(function (r) {
            $tb.append(
                '<tr>' +
                '<td>' + (r.product_name || '') + '</td>' +
                '<td>' + Number(r.qty_sold || 0).toLocaleString() + '</td>' +
                '<td>' + money(r.net_revenue) + '</td>' +
                '<td>' + money(r.estimated_cogs) + '</td>' +
                '<td>' + money(r.estimated_margin) + '</td>' +
                '<td>' + (r.estimated_margin_pct != null ? r.estimated_margin_pct + '%' : '-') + '</td>' +
                '</tr>'
            );
        });
    }

    function fillSlowMovingTable(rows) {
        var $tb = $('#analyticsSlowMovingTable tbody').empty();
        if (!rows.length) {
            $tb.append('<tr><td colspan="5" class="text-muted">' + (cfg.labels.noData || '') + '</td></tr>');
            return;
        }
        rows.forEach(function (r) {
            $tb.append(
                '<tr>' +
                '<td>' + (r.product_name || '') + '</td>' +
                '<td>' + (r.warehouse_name || '') + '</td>' +
                '<td>' + Number(r.qty || r.quantity || 0).toLocaleString() + '</td>' +
                '<td>' + Number(r.days_idle || 0).toLocaleString() + '</td>' +
                '<td>' + (r.movement_class_label || r.movement_class || '') + '</td>' +
                '</tr>'
            );
        });
    }

    function renderInventory(data) {
        if (!data) {
            $('#analyticsInventoryBody').html('<p class="text-muted mb-0">' + (cfg.labels.noData || '') + '</p>');
            return;
        }
        $('#analyticsInventoryBody').html(
            '<div class="row g-3">' +
            '<div class="col-6"><div class="erp-stat-chip"><div><span class="erp-stat-label">Stock Value</span><span class="erp-stat-value">' + money(data.stock_value) + '</span></div></div></div>' +
            '<div class="col-6"><div class="erp-stat-chip"><div><span class="erp-stat-label">Low Stock</span><span class="erp-stat-value">' + Number(data.low_stock_count || 0) + '</span></div></div></div>' +
            '<div class="col-6"><div class="erp-stat-chip"><div><span class="erp-stat-label">Out of Stock</span><span class="erp-stat-value">' + Number(data.out_of_stock_count || 0) + '</span></div></div></div>' +
            '<div class="col-6"><div class="erp-stat-chip"><div><span class="erp-stat-label">In Stock</span><span class="erp-stat-value">' + Number(data.in_stock_count || 0) + '</span></div></div></div>' +
            '</div>'
        );
    }

    function renderLoyalty(data) {
        if (!data) {
            $('#analyticsLoyaltyBody').html('<p class="text-muted mb-0">' + (cfg.labels.noData || '') + '</p>');
            return;
        }
        $('#analyticsLoyaltyBody').html(
            '<div class="row g-3">' +
            '<div class="col-6"><strong>Points Used</strong><div>' + Number(data.points_used || 0).toLocaleString() + '</div></div>' +
            '<div class="col-6"><strong>Points Earned</strong><div>' + Number(data.points_earned || 0).toLocaleString() + '</div></div>' +
            '<div class="col-6"><strong>Loyalty Discount</strong><div>' + money(data.discount_given) + '</div></div>' +
            '<div class="col-6"><strong>Orders</strong><div>' + Number(data.order_count || 0).toLocaleString() + '</div></div>' +
            '</div>'
        );
    }

    function renderFinance(data) {
        if (!cfg.isFinance) {
            return;
        }
        if (!data || !Object.keys(data).length) {
            $('#analyticsFinanceBody').html('<p class="text-muted mb-0">' + (cfg.labels.noData || '') + '</p>');
            return;
        }
        $('#analyticsFinanceBody').html(
            '<div class="row g-3">' +
            '<div class="col-md-3"><strong>Net Profit</strong><div>' + money(data.net_profit) + '</div></div>' +
            '<div class="col-md-3"><strong>Gross Profit</strong><div>' + money(data.gross_profit) + '</div></div>' +
            '<div class="col-md-3"><strong>Expenses</strong><div>' + money(data.total_expenses) + '</div></div>' +
            '<div class="col-md-3"><strong>Cash / Bank</strong><div>' + money(data.cash_bank_balance) + '</div></div>' +
            '<div class="col-md-3"><strong>Receivables</strong><div>' + money((data.receivables && data.receivables.total) || 0) + '</div></div>' +
            '<div class="col-md-3"><strong>Payables</strong><div>' + money((data.payables && data.payables.total) || 0) + '</div></div>' +
            '</div>'
        );
    }

    function loadAll() {
        var salesP = fetchWidget('sales-overview');
        var purchasesP = fetchWidget('purchases-overview');
        var inventoryP = fetchWidget('inventory-summary');
        var financeP = cfg.isFinance ? fetchWidget('finance-summary') : $.Deferred().resolve({}).promise();
        var topProductsP = fetchWidget('top-products');
        var topCustomersP = fetchWidget('top-customers');
        var branchP = fetchWidget('branch-comparison');
        var sourceP = fetchWidget('order-source-breakdown');
        var paymentP = fetchWidget('payment-method-breakdown');
        var segmentsP = fetchWidget('customer-segments');
        var marginP = fetchWidget('product-margin');
        var slowP = fetchWidget('slow-moving');
        var loyaltyP = fetchWidget('loyalty-summary');

        $.when(salesP, purchasesP, inventoryP, financeP, marginP).done(function (s, p, i, f, m) {
            renderKpis(s[0] || s, p[0] || p, i[0] || i, f[0] || f, m[0] || m);
            renderTrend(s[0] || s);
        });

        topProductsP.done(function (rows) {
            var labels = (rows || []).map(rowName);
            var values = (rows || []).map(rowNet);
            renderBar('topProducts', '#analyticsTopProductsChart', 'analyticsTopProductsEmpty', labels, values, 'Net');
        });

        topCustomersP.done(function (rows) {
            var labels = (rows || []).map(function (r) { return r.customer || r.customer_name || ''; });
            var values = (rows || []).map(rowNet);
            renderBar('topCustomers', '#analyticsTopCustomersChart', 'analyticsTopCustomersEmpty', labels, values, 'Net');
        });

        branchP.done(function (rows) {
            var labels = (rows || []).map(function (r) { return r.branch_name || r.branch || r.name || ''; });
            var values = (rows || []).map(rowNet);
            renderBar('branch', '#analyticsBranchChart', 'analyticsBranchEmpty', labels, values, 'Net');
        });

        sourceP.done(function (rows) {
            var labels = (rows || []).map(function (r) { return r.order_source || r.order_source_name || r.name || ''; });
            var values = (rows || []).map(rowNet);
            renderDonut('source', '#analyticsOrderSourceChart', 'analyticsOrderSourceEmpty', labels, values);
        });

        paymentP.done(function (rows) {
            var labels = (rows || []).map(function (r) { return r.payment_method || r.payment_method_name || r.name || ''; });
            var values = (rows || []).map(function (r) { return Number(r.total_amount || r.net || r.total || r.amount || 0); });
            renderDonut('payment', '#analyticsPaymentMethodChart', 'analyticsPaymentMethodEmpty', labels, values);
        });

        segmentsP.done(renderSegments);
        marginP.done(fillMarginTable);
        slowP.done(fillSlowMovingTable);
        inventoryP.done(function (d) { renderInventory(d[0] || d); });
        loyaltyP.done(function (d) { renderLoyalty(d[0] || d); });
        financeP.done(function (d) { renderFinance(d[0] || d); });
    }

    $(function () {
        $('#toggleFilter').on('click', function () {
            $('#filterSection').slideToggle(150);
        });

        if ($('.select2').length && $.fn.select2) {
            $('.select2').select2({ width: '100%', allowClear: true, placeholder: '' });
        }

        var $form = $('#analyticsFilterForm');
        if ($form.length) {
            function submitWithDates() {
                if (typeof filterStartDate !== 'undefined' && filterStartDate) {
                    $('#dashboard_start_date').val(filterStartDate);
                }
                if (typeof filterEndDate !== 'undefined' && filterEndDate) {
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
        }

        $(document).on('click', '.analytics-export', function (e) {
            e.preventDefault();
            var widget = $(this).data('widget');
            window.location.href = cfg.exportUrl + '/' + widget + '?' + qs();
        });

        loadAll();
    });
})(jQuery);
