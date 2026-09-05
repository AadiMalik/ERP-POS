@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_employees.title') }}</h4>

    @if (session('generated_password'))
    <div class="alert alert-success">
        <strong>Employee account created.</strong> Share these first-login credentials with the employee -
        they will be required to set a new password on first login.<br>
        Email: <strong>{{ session('generated_email') }}</strong> &nbsp; | &nbsp;
        Temporary Password: <strong>{{ session('generated_password') }}</strong>
    </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    {{ __('common.filters') }}
                </button>
            </div>
            <div class="d-flex gap-2">
                @include('admin.partials.import-export-buttons', [
                    'importExportModule' => 'employee',
                    'importExportLabel' => __('hrm_employees.title'),
                    'importExportRefreshFn' => 'initDataTableemployee_table',
                ])
                @can('employee.create')
                <a href="{{ url('admin/employee/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    {{ __('common.add_new') }}
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('hrm_employees.department') }}</label>
                        <select id="department_id" class="form-select">
                            <option value="">{{ __('hrm_employees.all_departments') }}</option>
                            @foreach ($departments as $item)
                            <option value="{{ $item->department_id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.status') }}</label>
                        <select id="status" class="form-select">
                            <option value="">{{ __('common.all_statuses') }}</option>
                            <option value="active">{{ __('common.active') }}</option>
                            <option value="on_leave">On Leave</option>
                            <option value="suspended">Suspended</option>
                            <option value="resigned">Resigned</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="employee_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.email') }}</th>
                            <th>{{ __('hrm_employees.department') }}</th>
                            <th>{{ __('hrm_employees.designation') }}</th>
                            <th>{{ __('common.branch') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.action') }}</th>
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
{data:'email',name:'email'},
{data:'department',name:'department',sortable:false},
{data:'designation',name:'designation',sortable:false},
{data:'branch',name:'branch',sortable:false},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'employee/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'employee_table',
'variable' => 'employee_table',
'datefilter' => false,
'params' => "department_id:$('#department_id').val(),status:$('#status').val()",
])
<script>
    $(document).ready(function() {
        $('#department_id').select2();
        $('#status').select2();
    });
    $('#search_btn').click(function() {
        initDataTableemployee_table();
    });
    deleteRecord({
        buttonClass: "#deleteEmployee",
        url: url_local + "/admin/employee",
        tableCallback: function() {
            initDataTableemployee_table();
        }
    });

    $(document).on('change', '.employeeStatusSelect', function() {
        let id = $(this).data('id');
        let status = $(this).val();
        ajaxRequest({
                url: url_local + '/admin/employee/change-status/' + id,
                method: 'POST',
                data: {
                    status: status
                }
            })
            .then((response) => {
                successMessage(response.Message);
            })
            .catch((err) => {
                errorMessage(err.Message);
                initDataTableemployee_table();
            });
    });
</script>
@endsection
