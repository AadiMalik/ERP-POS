@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_leaves.title') }}</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    {{ __('common.filters') }}
                </button>
            </div>
            @can('leave-request.create')
            <a href="{{ url('admin/leave-request/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i>
                {{ __('common.add_new') }}
            </a>
            @endcan
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.employee') }}</label>
                        <select id="employee_id" class="form-select">
                            <option value="">{{ __('common.all_employees') }}</option>
                            @foreach ($employees as $item)
                            <option value="{{ $item->employee_id }}">{{ $item->user->name ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.status') }}</label>
                        <select id="status" class="form-select">
                            <option value="">{{ __('common.all_status') }}</option>
                            <option value="pending">{{ __('hrm_leaves.pending') }}</option>
                            <option value="approved">{{ __('hrm_leaves.approved') }}</option>
                            <option value="rejected">{{ __('hrm_leaves.rejected') }}</option>
                            <option value="cancelled">{{ __('hrm_leaves.cancelled') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="leave_request_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('common.employee') }}</th>
                            <th>{{ __('hrm_leaves.leave_type') }}</th>
                            <th>{{ __('hrm_leaves.start') }}</th>
                            <th>{{ __('hrm_leaves.end') }}</th>
                            <th>{{ __('hrm_leaves.days') }}</th>
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
<script>
    window.i18n_hrm_leaves = {
        approve_confirm: @json(__('hrm_leaves.approve_confirm')),
        reject_confirm: @json(__('hrm_leaves.reject_confirm')),
        remarks_placeholder: @json(__('hrm_leaves.remarks_placeholder')),
        approve: @json(__('hrm_leaves.approve')),
        reject: @json(__('hrm_leaves.reject')),
        cancel: @json(__('common.cancel')),
    };
</script>
@include('admin.partials.datatable', [
'columns' => "
{data:'employee',name:'employee',sortable:false},
{data:'leave_type',name:'leave_type',sortable:false},
{data:'start_date',name:'start_date'},
{data:'end_date',name:'end_date'},
{data:'days_count',name:'days_count'},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'leave-request/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'leave_request_table',
'variable' => 'leave_request_table',
'datefilter' => false,
'params' => "employee_id:$('#employee_id').val(),status:$('#status').val()",
])
<script>
    $(document).ready(function() {
        $('#employee_id').select2();
        $('#status').select2();
    });
    $('#search_btn').click(function() {
        initDataTableleave_request_table();
    });

    function decideLeave(id, status) {
        Swal.fire({
            title: status === 'approved' ? window.i18n_hrm_leaves.approve_confirm : window.i18n_hrm_leaves.reject_confirm,
            input: 'text',
            inputPlaceholder: window.i18n_hrm_leaves.remarks_placeholder,
            showCancelButton: true,
            confirmButtonText: status === 'approved' ? window.i18n_hrm_leaves.approve : window.i18n_hrm_leaves.reject,
            cancelButtonText: window.i18n_hrm_leaves.cancel,
        }).then((result) => {
            if (result.isConfirmed) {
                ajaxRequest({
                        url: url_local + '/admin/leave-request/' + id + '/decide',
                        method: 'POST',
                        data: {
                            status: status,
                            remarks: result.value
                        }
                    })
                    .then((response) => {
                        successMessage(response.Message);
                        initDataTableleave_request_table();
                    })
                    .catch((err) => {
                        errorMessage(err.Message);
                    });
            }
        });
    }

    $(document).on('click', '#approveLeaveRequest', function() {
        decideLeave($(this).data('id'), 'approved');
    });
    $(document).on('click', '#rejectLeaveRequest', function() {
        decideLeave($(this).data('id'), 'rejected');
    });

    deleteRecord({
        buttonClass: "#deleteLeaveRequest",
        url: url_local + "/admin/leave-request",
        tableCallback: function() {
            initDataTableleave_request_table();
        }
    });
</script>
@endsection
