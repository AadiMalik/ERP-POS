/**
 * Reusable "View JV" popup. Any page can add a button/link like:
 *   <button type="button" class="view-jv-btn" data-source-type="Sale" data-source-id="{{ $order->order_id }}">View JV</button>
 * or call openJvModal(sourceType, sourceId) directly from its own JS.
 * Backed by the single generic endpoint POST /admin/journal-voucher/view.
 */
function openJvModal(sourceType, sourceId) {
    var modalEl = document.getElementById('viewJvModal');
    if (!modalEl) {
        return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    document.getElementById('viewJvLoading').style.display = '';
    document.getElementById('viewJvContent').style.display = 'none';
    document.getElementById('viewJvError').style.display = 'none';

    modal.show();

    ajaxRequest({
        url: url_local + '/admin/journal-voucher/view',
        method: 'POST',
        data: {
            source_type: sourceType,
            source_id: sourceId
        }
    }).then(function(response) {
        renderJvDetail(response.Data || {});
    }).catch(function(error) {
        document.getElementById('viewJvLoading').style.display = 'none';
        var errorBox = document.getElementById('viewJvError');
        errorBox.style.display = '';
        errorBox.textContent = (error && error.responseJSON && error.responseJSON.Message) ?
            error.responseJSON.Message : 'Unable to load this Journal Voucher.';
    });
}

function renderJvDetail(data) {
    document.getElementById('viewJvLoading').style.display = 'none';
    document.getElementById('viewJvContent').style.display = '';

    document.getElementById('jv_entry_no').textContent = data.entry_no || '-';
    document.getElementById('jv_journal_short').textContent = data.journal_short || data.journal_name || '-';
    document.getElementById('jv_entry_date').textContent = data.entry_date || '-';
    document.getElementById('jv_status').textContent = data.status || '-';
    document.getElementById('jv_reference_no').textContent = data.reference_no || '-';
    document.getElementById('jv_source_type').textContent = data.source_type || '-';
    document.getElementById('jv_description').textContent = data.description || '-';

    var body = document.getElementById('jv_lines_body');
    body.innerHTML = '';

    (data.lines || []).forEach(function(line) {
        var row = document.createElement('tr');
        row.innerHTML =
            '<td>' + (line.account_name || '') + '</td>' +
            '<td>' + (line.description || '') + '</td>' +
            '<td class="text-end">' + decimal(line.debit || 0) + '</td>' +
            '<td class="text-end">' + decimal(line.credit || 0) + '</td>';
        body.appendChild(row);
    });

    document.getElementById('jv_total_debit').textContent = decimal(data.total_debit || 0);
    document.getElementById('jv_total_credit').textContent = decimal(data.total_credit || 0);
}

document.addEventListener('DOMContentLoaded', function() {
    document.body.addEventListener('click', function(e) {
        var btn = e.target.closest('.view-jv-btn');
        if (btn) {
            openJvModal(btn.getAttribute('data-source-type'), btn.getAttribute('data-source-id'));
        }
    });
});
