@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Fixed Asset Depreciation</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i> Filters
                </button>
            </div>
            <div class="d-flex gap-2">
                @canAccess('fixed-asset-depreciation.create')
                <a href="{{ url('admin/fixed-asset-depreciation/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i> Post Depreciation
                </a>
                @endcanAccess
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
                            <option value="{{ $item->business_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
                        <select id="branch_id" class="form-select">
                            <option value="">--All Branches--</option>
                            @if (RoleNames::SUPERADMIN != getRoleName())
                            @foreach ($branches as $item)
                            <option value="{{ $item->branch_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
                            @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fixed Asset</label>
                        <select id="fixed_asset_id" class="form-select">
                            <option value="">--All Assets--</option>
                            @foreach ($assets as $item)
                            <option value="{{ $item->fixed_asset_id }}">
                                {{ $item->asset_code ? $item->asset_code . ' - ' : '' }}{{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        @include('admin.partials.date_filter')
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="fixed_asset_depreciation_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Period</th>
                            <th>Asset Code</th>
                            <th>Asset Name</th>
                            <th>Branch</th>
                            <th>Previous Value</th>
                            <th>Depreciation</th>
                            <th>New Value</th>
                            <th>Accumulated</th>
                            <th>JV</th>
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
{data:'depreciation_date',name:'depreciation_date'},
{data:'period_key',name:'period_key'},
{data:'asset_code',name:'asset_code',sortable:false},
{data:'asset_name',name:'asset_name',sortable:false},
{data:'branch',name:'branch',sortable:false},
{data:'previous_value',name:'previous_value',sortable:false},
{data:'depreciation_amount',name:'depreciation_amount',sortable:false},
{data:'new_value',name:'new_value',sortable:false},
{data:'accumulated_depreciation',name:'accumulated_depreciation',sortable:false},
{data:'journal_entry',name:'journal_entry',sortable:false},
{data:'status',name:'status',sortable:false},
{data:'action',name:'action',sortable:false}",
'route' => 'fixed-asset-depreciation/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'fixed_asset_depreciation_table',
'variable' => 'fixed_asset_depreciation_table',
'datefilter' => true,
'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),fixed_asset_id:$('#fixed_asset_id').val()",
])
<script>
$(document).ready(function() {
    $('#business_id').select2();
    $('#branch_id').select2();
    $('#fixed_asset_id').select2();

    $('#toggleFilter').on('click', function() {
        $('#filterSection').slideToggle();
    });

    $('#search_btn').on('click', function() {
        initDataTablefixed_asset_depreciation_table();
    });

    $('#reset_filter').on('click', function() {
        $('#business_id').val('').trigger('change');
        $('#branch_id').val('').trigger('change');
        $('#fixed_asset_id').val('').trigger('change');
        initDataTablefixed_asset_depreciation_table();
    });
});

deleteRecord({
    buttonClass: ".delete",
    url: url_local + "/admin/fixed-asset-depreciation",
    tableCallback: function() {
        initDataTablefixed_asset_depreciation_table();
    }
});
</script>
@endsection
