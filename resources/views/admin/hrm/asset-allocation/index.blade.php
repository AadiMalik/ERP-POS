@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Asset Allocation</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <select id="employee_id" class="form-select select2" style="width:220px;">
                    <option value="">--All Employees--</option>
                    @foreach ($employees as $item)
                    <option value="{{ $item->employee_id }}">{{ $item->user->name ?? '-' }}</option>
                    @endforeach
                </select>
            </div>
            @can('asset-allocation.create')
            <a href="{{ url('admin/asset-allocation/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i>
                Issue Asset
            </a>
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive p-4">
                <table id="asset_allocation_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Asset</th>
                            <th>Employee</th>
                            <th>Issue Date</th>
                            <th>Expected Return</th>
                            <th>Return Date</th>
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
        let condition = prompt('Condition on return (new, good, fair, damaged, lost):', 'good');
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
