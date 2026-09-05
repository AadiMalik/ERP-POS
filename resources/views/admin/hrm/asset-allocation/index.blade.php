@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_assets.allocation_title') }}</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <select id="employee_id" class="form-select select2" style="width:220px;">
                    <option value="">{{ __('common.all_employees') }}</option>
                    @foreach ($employees as $item)
                    <option value="{{ $item->employee_id }}">{{ $item->user->name ?? '-' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex gap-2">
                @include('admin.partials.import-export-buttons', [
                    'importExportModule' => 'asset-allocation',
                    'importExportLabel' => __('hrm_assets.allocation_import_export_label'),
                    'importExportRefreshFn' => 'initDataTableasset_allocation_table',
                ])
                @can('asset-allocation.create')
                <a href="{{ url('admin/asset-allocation/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    {{ __('hrm_assets.issue_asset') }}
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive p-4">
                <table id="asset_allocation_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('hrm_assets.singular') }}</th>
                            <th>{{ __('common.employee') }}</th>
                            <th>{{ __('hrm_assets.issue_date') }}</th>
                            <th>{{ __('hrm_assets.expected_return') }}</th>
                            <th>{{ __('hrm_assets.return_date') }}</th>
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
    window.i18n_hrm_assets = {
        condition_on_return_prompt: @json(__('hrm_assets.condition_on_return_prompt')),
    };
</script>
@include('admin.partials.datatable', [
'columns' => "
{data:'asset',name:'asset',sortable:false},
{data:'employee',name:'employee',sortable:false},
{data:'issue_date',name:'issue_date'},
{data:'expected_return_date',name:'expected_return_date'},
{data:'return_date',name:'return_date'},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'asset-allocation/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'asset_allocation_table',
'variable' => 'asset_allocation_table',
'datefilter' => false,
'params' => "employee_id:$('#employee_id').val()",
])
<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
    $('#employee_id').change(function() {
        initDataTableasset_allocation_table();
    });

    $(document).on('click', '#returnAllocation', function() {
        let id = $(this).data('id');
        let condition = prompt(window.i18n_hrm_assets.condition_on_return_prompt, 'good');
        if (condition === null) return;
        ajaxRequest({
                url: url_local + '/admin/asset-allocation/' + id + '/return',
                method: 'POST',
                data: { condition_on_return: condition }
            })
            .then((response) => {
                successMessage(response.Message);
                initDataTableasset_allocation_table();
            })
            .catch((err) => errorMessage(err.Message));
    });
</script>
@endsection
