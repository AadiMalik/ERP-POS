@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_advances.title') }}</h4>
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
                    'importExportModule' => 'employee-advance',
                    'importExportLabel' => __('hrm_advances.import_export_label'),
                    'importExportRefreshFn' => 'initDataTableemployee_advance_table',
                ])
                @can('employee-advance.create')
                <a href="{{ url('admin/employee-advance/create') }}" class="btn btn-primary rounded-pill">
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
                            <option value="pending">{{ __('hrm_advances.pending') }}</option>
                            <option value="repaying">{{ __('hrm_advances.repaying') }}</option>
                            <option value="completed">{{ __('hrm_advances.completed') }}</option>
                            <option value="rejected">{{ __('hrm_advances.rejected') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="employee_advance_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('common.employee') }}</th>
                            <th>{{ __('common.amount') }}</th>
                            <th>{{ __('hrm_advances.installments') }}</th>
                            <th>{{ __('hrm_advances.remaining') }}</th>
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
<script>
    window.i18n_hrm_advances = {
        installments_prompt: @json(__('hrm_advances.installments_prompt')),
        approve_confirm: @json(__('hrm_advances.approve_confirm')),
        reject_confirm: @json(__('hrm_advances.reject_confirm')),
        approve: @json(__('hrm_advances.approve')),
        reject: @json(__('hrm_advances.reject')),
    };
</script>
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
            installments = prompt(window.i18n_hrm_advances.installments_prompt, '1');
            if (installments === null) return;
        }
        Swal.fire({
            title: status === 'approved' ? window.i18n_hrm_advances.approve_confirm : window.i18n_hrm_advances.reject_confirm,
            showCancelButton: true,
            confirmButtonText: status === 'approved' ? window.i18n_hrm_advances.approve : window.i18n_hrm_advances.reject,
            cancelButtonText: window.i18n?.cancel || 'Cancel',
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
