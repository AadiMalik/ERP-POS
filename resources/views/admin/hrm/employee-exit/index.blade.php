@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_exit.title') }}</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <select id="status" class="form-select" style="width:200px;">
                    <option value="">{{ __('common.all_status') }}</option>
                    <option value="pending">{{ __('hrm_exit.pending') }}</option>
                    <option value="approved">{{ __('hrm_exit.approved') }}</option>
                    <option value="rejected">{{ __('hrm_exit.rejected') }}</option>
                    <option value="finalized">{{ __('hrm_exit.finalized') }}</option>
                </select>
            </div>
            @can('employee-exit.create')
            <a href="{{ url('admin/employee-exit/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i>
                {{ __('hrm_exit.new_request') }}
            </a>
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive p-4">
                <table id="employee_exit_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('common.employee') }}</th>
                            <th>{{ __('common.type') }}</th>
                            <th>{{ __('hrm_exit.request_date') }}</th>
                            <th>{{ __('hrm_exit.last_working_date') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.action') }}</th>
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
