@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Leave Requests</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    Filters
                </button>
            </div>
            @can('leave-request.create')
            <a href="{{ url('admin/leave-request/create') }}" class="btn btn-primary rounded-pill">
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
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="status" class="form-select">
                            <option value="">--All Status--</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="leave_request_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Days</th>
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
            title: status === 'approved' ? 'Approve this leave request?' : 'Reject this leave request?',
            input: 'text',
            inputPlaceholder: 'Remarks (optional)',
            showCancelButton: true,
            confirmButtonText: status === 'approved' ? 'Approve' : 'Reject',
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
