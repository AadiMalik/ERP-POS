@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center py-3 mb-4">
            <h4 class="fw-bold mb-0">
                Subscription Invoices
                @if (($pendingCount ?? 0) > 0)
                    <span class="badge badge-danger bg-danger rounded-pill" id="invoicesPagePendingBadge">{{ $pendingCount }}</span>
                @endif
            </h4>
            <p class="text-muted mb-0 small">Confirm / reject payments from the list actions or the invoice detail page.</p>
        </div>
        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2">
                <select id="filter_request_type" class="form-select" style="width:160px">
                    <option value="">All Types</option>
                    <option value="new" @selected(($defaultRequestType ?? '') === 'new')>New</option>
                    <option value="renew" @selected(($defaultRequestType ?? '') === 'renew')>Renew</option>
                </select>
                <select id="filter_status" class="form-select" style="width:160px">
                    <option value="">All Invoice Status</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="partially_paid">Partially Paid</option>
                    <option value="paid">Paid</option>
                    <option value="void">Void</option>
                </select>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="filter_payment_pending" @checked($defaultPaymentPending ?? true)>
                    <label class="form-check-label" for="filter_payment_pending">Pending payments only</label>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="invoices_table" class="table datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Business</th>
                            <th>Package</th>
                            <th>Type</th>
                            <th>Total</th>
                            <th>Invoice</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
        {data:'invoice_no',name:'invoice_no'},
        {data:'business',name:'business'},
        {data:'package',name:'package'},
        {data:'request_type',name:'request_type',sortable:false,searchable:false},
        {data:'total',name:'total'},
        {data:'status',name:'status'},
        {data:'payment_status',name:'payment_status',sortable:false,searchable:false},
        {data:'date_created',name:'date_created'},
        {data:'action',name:'action',sortable:false,searchable:false}",
        'route' => 'subscription-invoices/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'invoices_table',
        'variable' => 'invoices_table',
        'params' => "status:$('#filter_status').val(),request_type:$('#filter_request_type').val(),payment_pending:$('#filter_payment_pending').is(':checked')?1:0",
    ])
    <script>
        function refreshPendingBadge() {
            ajaxRequest({
                url: `${url_local}/admin/subscription-invoices/pending-count`,
                method: 'GET'
            }).then(function (res) {
                var count = (res.Data && res.Data.count != null) ? res.Data.count : 0;
                var $page = $('#invoicesPagePendingBadge');
                var $side = $('#subscriptionInvoicesPendingBadge');
                if (count > 0) {
                    if ($page.length) { $page.text(count).show(); }
                    else {
                        $('h4.fw-bold').first().append(' <span class="badge badge-danger bg-danger rounded-pill" id="invoicesPagePendingBadge">' + count + '</span>');
                    }
                    if ($side.length) { $side.text(count).show(); }
                } else {
                    $page.remove();
                    $side.hide().text('0');
                }
            }).catch(function () {});
        }

        $(function () {
            $('#filter_status, #filter_request_type, #filter_payment_pending').on('change', function () {
                initDataTableinvoices_table();
            });

            $(document).on('click', '.delete-invoice', function () {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Delete this invoice?',
                    text: 'Related payments will also be deleted.',
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                    confirmButtonColor: '#d33'
                }).then((result) => {
                    if (!result.isConfirmed) return;
                    ajaxRequest({
                        url: `${url_local}/admin/subscription-invoices/${id}`,
                        method: 'DELETE'
                    }).then(() => {
                        successMessage('Invoice deleted');
                        initDataTableinvoices_table();
                        refreshPendingBadge();
                    }).catch((e) => errorMessage(e.Message ?? 'Something went wrong'));
                });
            });

            $(document).on('click', '.approve-payment-row', function () {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Confirm this payment?',
                    text: 'Business will be activated and an email with invoice will be sent.',
                    showCancelButton: true,
                    confirmButtonText: 'Confirm'
                }).then((result) => {
                    if (!result.isConfirmed) return;
                    ajaxRequest({
                        url: `${url_local}/admin/subscription-payments/${id}/approve`,
                        method: 'POST'
                    }).then(() => {
                        successMessage('Payment confirmed');
                        initDataTableinvoices_table();
                        refreshPendingBadge();
                    }).catch((e) => errorMessage(e.Message ?? 'Something went wrong'));
                });
            });

            $(document).on('click', '.reject-payment-row', function () {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Reject this payment?',
                    input: 'text',
                    inputPlaceholder: 'Reason',
                    showCancelButton: true,
                    confirmButtonText: 'Reject'
                }).then((result) => {
                    if (!result.isConfirmed) return;
                    ajaxRequest({
                        url: `${url_local}/admin/subscription-payments/${id}/reject`,
                        method: 'POST',
                        data: { reason: result.value }
                    }).then(() => {
                        successMessage('Payment rejected');
                        initDataTableinvoices_table();
                        refreshPendingBadge();
                    }).catch((e) => errorMessage(e.Message ?? 'Something went wrong'));
                });
            });
        });
    </script>
@endsection
