@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        Fixed Assets
    </h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    Filters
                </button>
            </div>
            <div class="d-flex gap-2">
                @can('fixed-asset.create')
                <a href="{{ url('admin/fixed-asset/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    Add New
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    @if (RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.business') }}</label>
                        <select id="business_id" class="form-select">
                            <option value="">{{ __('common.all_businesses') }}</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}">{{ isset($item->code) ? $item->code : '' }}
                                {{ $item->name ?? '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.branch') }}</label>
                        <select id="branch_id" class="form-select">
                            <option value="">{{ __('common.all_branches') }}</option>
                            @if (RoleNames::SUPERADMIN != getRoleName())
                            @foreach ($branches as $item)
                            <option value="{{ $item->branch_id }}">{{ isset($item->code) ? $item->code : '' }}
                                {{ $item->name ?? '' }}
                            </option>
                            @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.category') }}</label>
                        <select id="fixed_asset_category_id" class="form-select">
                            <option value="">{{ __('common.all_categories') }}</option>
                            @foreach ($categories as $item)
                            <option value="{{ $item->fixed_asset_category_id }}">
                                {{ $item->code ? $item->code . ' ' : '' }}{{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('fixed_assets.depreciation_status') }}</label>
                        <select id="depreciation_status" class="form-select">
                            <option value="">{{ __('common.all_statuses') }}</option>
                            @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.purchase_date') }}</label>
                        @include('admin.partials.date_filter')
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">
                            Search
                        </button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                            Reset
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="fixed_asset_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('common.code') }}</th>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.category') }}</th>
                            <th>{{ __('common.branch') }}</th>
                            <th>{{ __('common.purchase_date') }}</th>
                            <th>{{ __('fixed_assets.purchase_cost') }}</th>
                            <th>{{ __('fixed_assets.current_value') }}</th>
                            <th>{{ __('fixed_assets.previous_value') }}</th>
                            <th>{{ __('fixed_assets.depreciation_amount') }}</th>
                            <th>{{ __('fixed_assets.accumulated_dep') }}</th>
                            <th>{{ __('fixed_assets.residual') }}</th>
                            <th>{{ __('common.frequency') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('fixed_assets.next_dep_date') }}</th>
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
{data:'asset_code',name:'asset_code'},
{data:'name',name:'name'},
{data:'category',name:'category',sortable:false},
{data:'branch',name:'branch',sortable:false},
{data:'purchase_date',name:'purchase_date'},
{data:'purchase_cost',name:'purchase_cost'},
{data:'current_book_value',name:'current_book_value'},
{data:'previous_book_value',name:'previous_book_value'},
{data:'last_depreciation_amount',name:'last_depreciation_amount'},
{data:'accumulated_depreciation',name:'accumulated_depreciation'},
{data:'residual_value',name:'residual_value'},
{data:'depreciation_frequency',name:'depreciation_frequency'},
{data:'depreciation_status',name:'depreciation_status',sortable:false},
{data:'next_depreciation_date',name:'next_depreciation_date'},
{data:'action',name:'action',sortable:false}",
'route' => 'fixed-asset/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'fixed_asset_table',
'variable' => 'fixed_asset_table',
'datefilter' => true,
'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),fixed_asset_category_id:$('#fixed_asset_category_id').val(),depreciation_status:$('#depreciation_status').val()",
])

<script>
    $(document).ready(function() {
        $('#business_id').select2();
        $('#branch_id').select2();
        $('#fixed_asset_category_id').select2();
        $('#depreciation_status').select2();
    });

    $('#search_btn').click(function() {
        initDataTablefixed_asset_table();
    });

    $('#reset_filter').click(function() {
        $('#business_id').val('').trigger('change');
        $('#branch_id').val('').trigger('change');
        $('#fixed_asset_category_id').val('').trigger('change');
        $('#depreciation_status').val('').trigger('change');
        initDataTablefixed_asset_table();
    });

    $('#business_id').change(function() {
        let business_id = $(this).val();
        if (!business_id) {
            $('#branch_id').html('<option value="">{{ __('common.all_branches') }}</option>');
            return;
        }
        ajaxRequest({
                url: url_local + '/admin/branch/by-business/' + business_id,
                data: {}
            })
            .then((response) => {
                let data = response.Data;
                let options = '<option value="">{{ __('common.all_branches') }}</option>';
                $.each(data, function(index, item) {
                    options += `<option value="${item.branch_id}">${item.name}</option>`;
                });
                $('#branch_id').html(options);
            })
            .catch((err) => {
                errorMessage(err.Message);
            });
    });

    deleteRecord({
        buttonClass: ".delete",
        url: url_local + "/admin/fixed-asset",
        tableCallback: function() {
            initDataTablefixed_asset_table();
        }
    });
</script>
@endsection
