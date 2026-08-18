@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Resignation / Termination</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <select id="status" class="form-select" style="width:200px;">
                    <option value="">--All Status--</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="finalized">Finalized</option>
                </select>
            </div>
            @can('employee-exit.create')
            <a href="{{ url('admin/employee-exit/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i>
                New Request
            </a>
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive p-4">
                <table id="employee_exit_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Request Date</th>
                            <th>Last Working Date</th>
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
{data:'type',name:'type'},
{data:'request_date',name:'request_date'},
{data:'last_working_date',name:'last_working_date'},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'employee-exit/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'employee_exit_table',
'variable' => 'employee_exit_table',
'datefilter' => false,
'params' => "status:$('#status').val()",
])
<script>
    $('#status').change(function() {
        initDataTableemployee_exit_table();
    });
</script>
@endsection
