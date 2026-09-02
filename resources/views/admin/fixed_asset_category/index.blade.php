@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        Fixed Asset Categories
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
                @can('fixed-asset-category.create')
                <a href="{{ url('admin/fixed-asset-category/create') }}" class="btn btn-primary rounded-pill">
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
                        <label class="form-label">Business</label>
                        <select id="business_id" class="form-select">
                            <option value="">--All Businesses--</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}">{{ isset($item->code) ? $item->code : '' }}
                                {{ $item->name ?? '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
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
                <table id="fixed_asset_category_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Useful Life (Years)</th>
                            <th>Residual %</th>
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
{data:'code',name:'code'},
{data:'name',name:'name'},
{data:'default_useful_life_years',name:'default_useful_life_years'},
{data:'default_residual_percent',name:'default_residual_percent'},
{data:'status',name:'status',sortable:false},
{data:'action',name:'action',sortable:false}",
'route' => 'fixed-asset-category/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'fixed_asset_category_table',
'variable' => 'fixed_asset_category_table',
'params' => "business_id:$('#business_id').val()",
])

<script>
    $(document).ready(function() {
        $('#business_id').select2();
    });

    $('#search_btn').click(function() {
        initDataTablefixed_asset_category_table();
    });

    $('#reset_filter').click(function() {
        $('#business_id').val('').trigger('change');
        initDataTablefixed_asset_category_table();
    });

    $(document).on('change', '.change-status', function() {
        let id = $(this).data('id');
        ajaxRequest({
                url: url_local + '/admin/fixed-asset-category/status/' + id,
                method: 'GET',
                data: {}
            })
            .then((response) => {
                successMessage(response.Message || 'Status Updated');
                initDataTablefixed_asset_category_table();
            })
            .catch((err) => {
                errorMessage(err.Message || 'Status update failed');
                initDataTablefixed_asset_category_table();
            });
    });

    deleteRecord({
        buttonClass: ".delete",
        url: url_local + "/admin/fixed-asset-category",
        tableCallback: function() {
            initDataTablefixed_asset_category_table();
        }
    });
</script>
@endsection
