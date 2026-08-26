@php
use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Homepage Sections</h4>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i> Filters
                </button>
            </div>
            <div>
                <a href="javascript:void(0)" id="createNewWebsiteSection" class="btn rounded-pill btn-primary">
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
                            <option value="{{ $item->business_id }}">{{ $item->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select id="filter_type" class="form-select">
                            <option value="">--All Types--</option>
                            @foreach ($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive text-nowrap p-4">
                <table id="website_section_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Heading</th>
                            <th>Image</th>
                            <th>Sort Order</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @include('admin.website_section.model.create')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/website_section.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'type' , name: 'type'},
{data: 'heading' , name: 'heading'},
{data: 'image' , name: 'image', 'sortable': false , searchable: false},
{data: 'sort_order' , name: 'sort_order'},
{data: 'status' , name: 'status', 'sortable': false , searchable: false},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'website-section/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'website_section_table',
'variable' => 'website_section_table',
'params' => "business_id:$('#filter_business_id').val(), type:$('#filter_type').val()",
])
<script>
    $(document).ready(function() {
        $('#business_id').select2({ dropdownParent: $('#ajaxModel') });
        $('#filter_business_id').select2();
    });
    $('#toggleFilter').click(function() { $('#filterSection').slideToggle(); });
    $('#search_btn').click(function() { initDataTablewebsite_section_table(); });
    $('#reset_filter').click(function() {
        $('#filter_business_id').val('').trigger('change');
        $('#filter_type').val('');
        initDataTablewebsite_section_table();
    });
</script>
@endsection
