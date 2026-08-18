@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Employee Deductions</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    Filters
                </button>
            </div>
            @can('employee-deduction.create')
            <a href="{{ url('admin/employee-deduction/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i>
                Add New
            </a>
            @endcan
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Employee</label>
                        <select id="employee_id" class="form-select">
                            <option value="">--All Employees--</option>
                            @foreach ($employees as $item)
                            <option value="{{ $item->employee_id }}">{{ $item->user->name ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="employee_deduction_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Title</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Status</th>
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
{data:'employee',name:'employee',sortable:false},
{data:'title',name:'title'},
{data:'amount',name:'amount'},
{data:'is_recurring',name:'is_recurring',sortable:false},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'employee-deduction/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'employee_deduction_table',
'variable' => 'employee_deduction_table',
'datefilter' => false,
'params' => "employee_id:$('#employee_id').val()",
])
<script>
    $(document).ready(function() {
        $('#employee_id').select2();
    });
    $('#search_btn').click(function() {
        initDataTableemployee_deduction_table();
    });
    deleteRecord({
        buttonClass: "#deleteEmployeeDeduction",
        url: url_local + "/admin/employee-deduction",
        tableCallback: function() {
            initDataTableemployee_deduction_table();
        }
    });
</script>
@endsection
