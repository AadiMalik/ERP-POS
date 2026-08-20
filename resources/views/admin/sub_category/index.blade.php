@php
use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('css')
@endsection
@section('content')
<!-- ========== table components start ========== -->
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Sub Categories</h4>

    <!-- Basic Bootstrap Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    Filters
                </button>

            </div>
            <div class="d-flex gap-2">
                @include('admin.partials.import-export-buttons', [
                    'importExportModule' => 'sub-category',
                    'importExportLabel' => 'Sub Categories',
                    'importExportRefreshFn' => 'initDataTablesub_category_table',
                    'importExportExportParamsSelector' => '#filter_business_id',
                ])
                <a href="javascript:void(0)" id="createNewSubCategory" class="btn rounded-pill btn-primary">
                    <i class="icon-base fa fa-plus mr-5"></i>Add New</a>
            </div>
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    @if (RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-3">
                        <label class="form-label">Business</label>
                        <select id="filter_business_id" class="form-select">
                            <option value="">--All Businesses--</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}">{{ isset($item->code) ? $item->code : '' }}
                                {{ $item->name ?? '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select id="filter_category_id" class="form-select">
                            <option value="">--All Categories--</option>
                            @foreach ($categories as $item)
                            <option value="{{ $item->category_id }}">
                                {{ $item->name ?? '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
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
            <div class="table-responsive text-nowrap p-4">
                <table id="sub_category_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Logo</th>
                            <th>Category</th>
                            <th>Business</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        <!-- end table row-->
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                <!-- end table -->
            </div>
        </div>
    </div>
    @include('admin/sub_category/model/create')
    @include('admin.partials.import-export-modal')
</div>
<!-- ========== table components end ========== -->
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/sub_category.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'name' , name: 'name'},
{data: 'logo' , name: 'logo', 'sortable': false , searchable: false},
{data: 'category' , name: 'category', 'sortable': false , searchable: false},
{data: 'business' , name: 'business', 'sortable': false , searchable: false},
{data: 'status' , name: 'status', 'sortable': false , searchable: false},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'sub-category/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'sub_category_table',
'variable' => 'sub_category_table',
'datefilter' => true,
'params' => "business_id:$('#filter_business_id').val(),category_id:$('#filter_category_id').val()",
])

<script>
    $(document).ready(function() {
        $('#business_id').select2({
            dropdownParent: $('#ajaxModel')
        });
        $('#category_id').select2({
            dropdownParent: $('#ajaxModel')
        });
        $('#filter_business_id').select2();
        $('#filter_category_id').select2();
    });
    $('#search_btn').click(function() {
        initDataTablesub_category_table();
    });
    $('#business_id').change(function() {
        let business_id = $(this).val();
        if (!business_id) {
            $('#category_id').html('<option value="">--Select Category--</option>');
            return;
        }
        ajaxRequest({
                url: url_local + '/admin/category/by-business/' + business_id,
                data: {}
            })
            .then((response) => {
                let data = response.Data;
                let options = '<option value="">--Select Category--</option>';
                $.each(data, function(index, item) {
                    options += `<option value="${item.category_id}">
                                        ${item.name}
                                    </option>
                                    `;
                });
                $('#category_id').html(options);
            })
            .catch((err) => {
                errorMessage(err.Message);
            });
    });
    $('#filter_business_id').change(function() {
        let business_id = $(this).val();
        if (!business_id) {
            $('#filter_category_id').html('<option value="">--All Categories--</option>');
            return;
        }
        ajaxRequest({
                url: url_local + '/admin/category/by-business/' + business_id,
                data: {}
            })
            .then((response) => {
                let data = response.Data;
                let options = '<option value="">--All Categories--</option>';
                $.each(data, function(index, item) {
                    options += `<option value="${item.category_id}">
                                        ${item.name}
                                    </option>
                                    `;
                });
                $('#filter_category_id').html(options);
            })
            .catch((err) => {
                errorMessage(err.Message);
            });
    });
</script>
@endsection