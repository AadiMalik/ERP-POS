@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('recurring_transactions.run_history') }} - {{ $rt->name }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-secondary">{{ \App\Enums\RecurringTransactionType::labels()[$rt->transaction_type] ?? $rt->transaction_type }}</span>
                    <span class="ms-2">Status: <strong>{{ ucfirst($rt->status) }}</strong></span>
                    <span class="ms-2">Occurrences: <strong>{{ $rt->occurrences_count }}</strong></span>
                    <span class="ms-2">Next Run: <strong>{{ $rt->next_run_date ? localDate($rt->next_run_date) : 'N/A' }}</strong></span>
                </div>
                <a href="{{ url('admin/recurring-transaction') }}" class="btn btn-outline-secondary">Back</a>
            </div>
            <div class="card-body">
                <div class="table-responsive p-4">
                    <table id="recurring_history_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Run Date</th>
                                <th>Status</th>
                                <th>Generated</th>
                                <th>Triggered By</th>
                                <th>Error</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'run_date',name:'run_date'},
                        {data:'status',name:'status',sortable:false},
                        {data:'generated',name:'generated',sortable:false},
                        {data:'triggered_by',name:'triggered_by',sortable:false},
                        {data:'error_message',name:'error_message',sortable:false}",
        'route' => 'history/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'recurring_history_table',
        'variable' => 'recurring_history_table',
    ])
@endsection
