@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Employee Advances</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    Filters
                </button>
            </div>
            @can('employee-advance.create')
            <a href="{{ url('admin/employee-advance/create') }}" class="btn btn-primary rounded-pill">
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
                            <option value="repaying">Repaying</option>
                            <option value="completed">Completed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="employee_advance_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Amount</th>
                            <th>Installments</th>
                            <th>Remaining</th>
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
{data:'amount',name:'amount'},
{data:'installments_count',name:'installments_count'},
{data:'remaining_balance',name:'remaining_balance'},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'employee-advance/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'employee_advance_table',
'variable' => 'employee_advance_table',
'datefilter' => false,
'params' => "employee_id:$('#employee_id').val(),status:$('#status').val()",
])
<script>
    $(document).ready(function() {
        $('#employee_id').select2();
        $('#status').select2();
    });
    $('#search_btn').click(function() {
        initDataTableemployee_advance_table();
    });

    function decideAdvance(id, status) {
        let installments = 1;
        if (status === 'approved') {
            installments = prompt('Number of installments to recover this advance over:', '1');
            if (installments === null) return;
        }
        Swal.fire({
            title: status === 'approved' ? 'Approve this advance?' : 'Reject this advance?',
            showCancelButton: true,
            confirmButtonText: status === 'approved' ? 'Approve' : 'Reject',
        }).then((result) => {
            if (result.isConfirmed) {
                ajaxRequest({
                        url: url_local + '/admin/employee-advance/' + id + '/decide',
                        method: 'POST',
                        data: {
                            status: status,
                            installments_count: installments
                        }
                    })
                    .then((response) => {
                        successMessage(response.Message);
                        initDataTableemployee_advance_table();
                    })
                    .catch((err) => {
                        errorMessage(err.Message);
                    });
            }
        });
    }

    $(document).on('click', '#approveAdvance', function() {
        decideAdvance($(this).data('id'), 'approved');
    });
    $(document).on('click', '#rejectAdvance', function() {
        decideAdvance($(this).data('id'), 'rejected');
    });

    deleteRecord({
        buttonClass: "#deleteAdvance",
        url: url_local + "/admin/employee-advance",
        tableCallback: function() {
            initDataTableemployee_advance_table();
        }
    });
</script>
@endsection
