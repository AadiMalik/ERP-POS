@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Recurring Transactions
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @include('admin.partials.import-export-buttons', [
                        'importExportModule' => 'recurring-transaction',
                        'importExportLabel' => 'Recurring Transactions',
                        'importExportRefreshFn' => 'initDataTablerecurring_transaction_table',
                        'importExportExportParamsSelector' => '#business_id',
                    ])
                    @can('recurring-transaction.create')
                        <a href="{{ url('admin/recurring-transaction/create') }}" class="btn btn-primary rounded-pill">
                            <i class="fa fa-plus"></i>
                            Add New
                        </a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">Business</label>
                                <select id="business_id" class="form-select">
                                    <option value="">--All Businesses--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ $item->code ?? '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Transaction Type</label>
                            <select id="transaction_type" class="form-select">
                                <option value="">--All Types--</option>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select id="status" class="form-select">
                                <option value="">--All Statuses--</option>
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Frequency</label>
                            <select id="frequency" class="form-select">
                                <option value="">--All Frequencies--</option>
                                @foreach ($frequencies as $item)
                                    <option value="{{ $item }}">{{ ucfirst($item) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Next Run From</label>
                            <input type="text" id="next_run_from" class="form-control datepicker">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Next Run To</label>
                            <input type="text" id="next_run_to" class="form-control datepicker">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">
                                Search
                            </button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="recurring_transaction_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Frequency</th>
                                <th>Next Run</th>
                                <th>Last Run</th>
                                <th>Branch</th>
                                <th>Business</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        @include('admin.partials.import-export-modal')
    </div>
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'name',name:'name'},
                        {data:'transaction_type',name:'transaction_type',sortable:false},
                        {data:'frequency',name:'frequency',sortable:false},
                        {data:'next_run_date',name:'next_run_date'},
                        {data:'last_run_date',name:'last_run_date',sortable:false},
                        {data:'branch',name:'branch',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'status',name:'status',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'recurring-transaction/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'recurring_transaction_table',
        'variable' => 'recurring_transaction_table',
        'params' =>
            "business_id:$('#business_id').val(),transaction_type:$('#transaction_type').val(),status:$('#status').val(),frequency:$('#frequency').val(),next_run_from:$('#next_run_from').val(),next_run_to:$('#next_run_to').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#transaction_type').select2();
            $('#status').select2();
            $('#frequency').select2();
        });
        $('#toggleFilter').click(function() {
            $('#filterSection').slideToggle();
        });
        $('#search_btn').click(function() {
            initDataTablerecurring_transaction_table();
        });
        $('#reset_filter').click(function() {
            $('#business_id, #transaction_type, #status, #frequency').val('').trigger('change');
            $('#next_run_from, #next_run_to').val('');
            initDataTablerecurring_transaction_table();
        });

        function runRecurringAction(url, id) {
            $.ajax({
                url: url_local + url + id,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
                success: function(response) {
                    if (response.Success === false) {
                        errorMessage(response.Message || 'Something went wrong.');
                        return;
                    }
                    successMessage(response.Message);
                    initDataTablerecurring_transaction_table();
                },
                error: function(error) {
                    errorMessage(error.responseJSON?.Message || 'Something went wrong.');
                }
            });
        }

        $(document).on('click', '.pauseRecurring', function() {
            runRecurringAction('/admin/recurring-transaction/pause/', $(this).data('id'));
        });
        $(document).on('click', '.resumeRecurring', function() {
            runRecurringAction('/admin/recurring-transaction/resume/', $(this).data('id'));
        });
        $(document).on('click', '.cancelRecurring', function() {
            if (confirm('Cancel this recurring schedule? This cannot be undone.')) {
                runRecurringAction('/admin/recurring-transaction/cancel/', $(this).data('id'));
            }
        });
        $(document).on('click', '.runNowRecurring', function() {
            if (confirm('Generate the transaction for this schedule now?')) {
                runRecurringAction('/admin/recurring-transaction/run-now/', $(this).data('id'));
            }
        });

        //delete
        deleteRecord({
            buttonClass: "#deleteRecurringTransaction",
            url: url_local + "/admin/recurring-transaction",

            tableCallback: function() {
                initDataTablerecurring_transaction_table();
            }
        });
    </script>
@endsection
