@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Productions</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i> Filters
                </button>
            </div>
            <div class="d-flex gap-2">
                @can('production.create')
                <a href="{{ url('admin/production/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i> Add Production
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    @if (RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-3">
                        <label class="form-label">Business</label>
                        <select id="business_id" class="form-select">
                            <option value="">--All Businesses--</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}">{{ $item->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
                        <select id="branch_id" class="form-select">
                            <option value="">--All Branches--</option>
                            @foreach ($branches as $item)
                            <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="status" class="form-select">
                            <option value="">--All Statuses--</option>
                            @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
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
                <table id="production_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Business</th>
                            <th>Branch</th>
                            <th>Product</th>
                            <th>Warehouse</th>
                            <th>Qty</th>
                            <th>Batch</th>
                            <th>Expiry</th>
                            <th>Unit Cost</th>
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
{data:'business',name:'business',sortable:false},
{data:'branch',name:'branch',sortable:false},
{data:'product',name:'product',sortable:false},
{data:'warehouse',name:'warehouse',sortable:false},
{data:'quantity',name:'quantity'},
{data:'batch_no',name:'batch_no'},
{data:'expiry_date',name:'expiry_date'},
{data:'unit_cost',name:'unit_cost'},
{data:'status',name:'status',sortable:false},
{data:'action',name:'action',sortable:false}",
'route' => 'production/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'production_table',
'variable' => 'production_table',
'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),status:$('#status').val()",
])
<script>
    $(document).ready(function() {
        $('#business_id').select2();
        $('#branch_id').select2();
        $('#status').select2();
    });
    $('#search_btn').click(function() { initDataTableproduction_table(); });
    $('#reset_filter').click(function() {
        $('#business_id, #branch_id, #status').val('').trigger('change');
        initDataTableproduction_table();
    });
</script>
@endsection
