@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Bank Reconciliation</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i> Filters
                </button>
                @canAccess('bank-reconciliation.create')
                    <a href="{{ route('bank-reconciliation.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> New Reconciliation
                    </a>
                @endcanAccess
            </div>
            <div class="card-body">
                <div id="filterSection" class="border-bottom pb-3 mb-3" style="display:none;">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">Business</label>
                                <select id="business_id" class="form-select">
                                    <option value="">--All Businesses--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Account</label>
                            <select id="account_id" class="form-select">
                                <option value="">--All Accounts--</option>
                                @foreach ($accounts as $item)
                                    <option value="{{ $item->account_id }}">{{ $item->code }} - {{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select id="status" class="form-select">
                                <option value="">--All--</option>
                                <option value="draft">Draft</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="bank_reconciliation_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Period</th>
                                <th>Statement Closing</th>
                                <th>Difference</th>
                                <th>Status</th>
                                <th>Completed</th>
                                <th>Action</th>
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
            {data:'account',name:'account',sortable:false},
            {data:'period',name:'period',sortable:false},
            {data:'statement_closing',name:'statement_closing',sortable:false},
            {data:'difference',name:'difference',sortable:false},
            {data:'status_badge',name:'status_badge',sortable:false},
            {data:'completed_info',name:'completed_info',sortable:false},
            {data:'action',name:'action',sortable:false}",
        'route' => 'bank-reconciliation/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'bank_reconciliation_table',
        'variable' => 'bank_reconciliation_table',
        'params' => "business_id:$('#business_id').val(),account_id:$('#account_id').val(),status:$('#status').val()",
    ])
    <script>
        $('#toggleFilter').on('click', function() {
            $('#filterSection').slideToggle();
        });
        $('#search_btn').on('click', function() {
            initDataTablebank_reconciliation_table();
        });
        $('#reset_filter').on('click', function() {
            $('#business_id,#account_id,#status').val('').trigger('change');
            initDataTablebank_reconciliation_table();
        });
        deleteRecord({
            buttonClass: "#deleteBankReconciliation",
            url: url_local + "/admin/bank-reconciliation",
            tableCallback: function() {
                initDataTablebank_reconciliation_table();
            }
        });
    </script>
@endsection
