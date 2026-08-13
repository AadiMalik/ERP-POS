@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Renewal Requests</h4>
        <div class="card">
            <div class="table-responsive p-4">
                <table id="renewal_requests_table" class="table datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Business</th>
                            <th>Requested Package</th>
                            <th>Status</th>
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
        {data:'business',name:'business'},
        {data:'requested_package',name:'requested_package'},
        {data:'status',name:'status'},
        {data:'action',name:'action',sortable:false,searchable:false}",
        'route' => 'subscription-renewal-requests/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'renewal_requests_table',
        'variable' => 'renewal_requests_table',
    ])

    <script>
        $(document).on('click', '.approve-renewal-request', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Approve this renewal request?',
                text: 'The subscription will be renewed immediately and marked as paid.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Approve'
            }).then((result) => {
                if (result.isConfirmed) {
                    ajaxRequest({
                        url: `${url_local}/admin/subscription-renewal-requests/${id}/approve`,
                        method: 'POST'
                    }).then(() => {
                        successMessage('Renewal request approved.');
                        renewal_requests_table.destroy();
                        initDataTablerenewal_requests_table();
                    }).catch((e) => errorMessage(e.Message ?? 'Something went wrong'));
                }
            });
        });

        $(document).on('click', '.reject-renewal-request', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Reject this renewal request?',
                input: 'text',
                inputPlaceholder: 'Reason',
                showCancelButton: true,
                confirmButtonText: 'Reject'
            }).then((result) => {
                if (result.isConfirmed) {
                    ajaxRequest({
                        url: `${url_local}/admin/subscription-renewal-requests/${id}/reject`,
                        method: 'POST',
                        data: { reason: result.value }
                    }).then(() => {
                        successMessage('Renewal request rejected.');
                        renewal_requests_table.destroy();
                        initDataTablerenewal_requests_table();
                    }).catch((e) => errorMessage(e.Message ?? 'Something went wrong'));
                }
            });
        });

        $(document).on('click', '.request-changes-renewal-request', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Request changes from the business',
                input: 'text',
                inputPlaceholder: 'What needs to change?',
                showCancelButton: true,
                confirmButtonText: 'Send'
            }).then((result) => {
                if (result.isConfirmed) {
                    ajaxRequest({
                        url: `${url_local}/admin/subscription-renewal-requests/${id}/request-changes`,
                        method: 'POST',
                        data: { notes: result.value }
                    }).then(() => {
                        successMessage('Changes requested.');
                        renewal_requests_table.destroy();
                        initDataTablerenewal_requests_table();
                    }).catch((e) => errorMessage(e.Message ?? 'Something went wrong'));
                }
            });
        });
    </script>
@endsection
