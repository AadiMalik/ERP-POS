/**
 * Reusable "Stock Consumption Details" popup. Any page can add a button:
 *   <button type="button" class="stock-consumption-btn" data-reference-type="Sale" data-reference-id="{{ $order->order_id }}">Stock Consumption</button>
 * or call openStockConsumptionModal(referenceType, referenceId) directly.
 * Backed by the single generic endpoint POST /admin/stock-consumption/view.
 */
function openStockConsumptionModal(referenceType, referenceId) {
    var modalEl = document.getElementById('stockConsumptionModal');
    if (!modalEl) {
        return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    document.getElementById('stockConsumptionLoading').style.display = '';
    document.getElementById('stockConsumptionTableWrap').style.display = 'none';
    document.getElementById('stockConsumptionError').style.display = 'none';

    modal.show();

    ajaxRequest({
        url: url_local + '/admin/stock-consumption/view',
        method: 'POST',
        data: {
            reference_type: referenceType,
            reference_id: referenceId
        }
    }).then(function(response) {
        renderStockConsumption(response.Data || []);
    }).catch(function(error) {
        document.getElementById('stockConsumptionLoading').style.display = 'none';
        var errorBox = document.getElementById('stockConsumptionError');
        errorBox.style.display = '';
        errorBox.textContent = (error && error.responseJSON && error.responseJSON.Message) ?
            error.responseJSON.Message : 'No stock consumption found for this record.';
    });
}

function renderStockConsumption(rows) {
    document.getElementById('stockConsumptionLoading').style.display = 'none';
    document.getElementById('stockConsumptionTableWrap').style.display = '';

    var body = document.getElementById('stockConsumptionBody');
    body.innerHTML = '';

    rows.forEach(function(row) {
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td>' + (row.transaction_date || '') + '</td>' +
            '<td>' + (row.warehouse || '') + '</td>' +
            '<td>' + (row.product || '') + '</td>' +
            '<td>' + (row.variation || '') + '</td>' +
            '<td>' + (row.batch || '') + '</td>' +
            '<td>' + decimal(row.quantity || 0) + '</td>' +
            '<td>' + (row.unit || '') + '</td>' +
            '<td>' + decimal(row.conversion_factor || 1) + '</td>' +
            '<td>' + decimal(row.base_quantity || 0) + '</td>' +
            '<td>' + decimal(row.unit_price || 0) + '</td>' +
            '<td>' + decimal(row.total_price || 0) + '</td>' +
            '<td>' + (row.reference || '') + '</td>';
        body.appendChild(tr);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.body.addEventListener('click', function(e) {
        var btn = e.target.closest('.stock-consumption-btn');
        if (btn) {
            openStockConsumptionModal(btn.getAttribute('data-reference-type'), btn.getAttribute('data-reference-id'));
        }
    });
});
