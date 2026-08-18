@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Employee Ledger</h4>
    <div class="card">
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Employee</label>
                    <select id="employee_id" class="form-select select2">
                        <option value="">--All Employees--</option>
                        @foreach ($employees as $item)
                        <option value="{{ $item->employee_id }}">{{ $item->user->name ?? '-' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" id="search_btn" class="btn btn-primary w-100">Search</button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="employee_ledger_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Balance</th>
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
{data:'employee',name:'employee',sortable:false},
{data:'entry_date',name:'entry_date'},
{data:'type',name:'type'},
{data:'description',name:'description',sortable:false},
{data:'debit',name:'debit'},
{data:'credit',name:'credit'},
{data:'balance_after',name:'balance_after'}",
'route' => 'employee-ledger/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'employee_ledger_table',
'variable' => 'employee_ledger_table',
'datefilter' => false,
'params' => "employee_id:$('#employee_id').val()",
])
<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
    $('#search_btn').click(function() {
        initDataTableemployee_ledger_table();
    });
</script>
@endsection
