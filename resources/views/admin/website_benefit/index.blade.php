@php
use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Content Cards</h4>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="d-flex gap-2">
                @if (RoleNames::SUPERADMIN == getRoleName())
                <div class="col-md-4">
                    <select id="filter_business_id" class="form-select">
                        <option value="">--All Businesses--</option>
                        @foreach ($business as $item)
                        <option value="{{ $item->business_id }}">{{ $item->name ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-5">
                    <select id="filter_group" class="form-select">
                        <option value="">--All Groups--</option>
                        @foreach ($groups as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <a href="javascript:void(0)" id="createNewBenefit" class="btn rounded-pill btn-primary">
                <i class="icon-base fa fa-plus mr-5"></i>Add New</a>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-4">
                <table id="benefit_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Icon</th>
                            <th>Group</th>
                            <th>Title</th>
                            <th>Description</th>
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
    @include('admin.website_benefit.model.create')
</div>
@endsection
@section('js')
<script>const WEBSITE_BENEFIT_GROUPS = @json($groups);</script>
<script src="{{ asset('public/assets/js/admin/website_benefit.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'preview' , name: 'preview', 'sortable': false , searchable: false},
{data: 'group' , name: 'group'},
{data: 'title' , name: 'title'},
{data: 'description' , name: 'description'},
{data: 'sort_order' , name: 'sort_order'},
{data: 'status' , name: 'status', 'sortable': false , searchable: false},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'website-benefit/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'benefit_table',
'variable' => 'benefit_table',
'params' => "business_id:$('#filter_business_id').val(), group:$('#filter_group').val()",
])
<script>
    $(document).ready(function() {
        $('#filter_business_id').select2().on('change', function() { initDataTablebenefit_table(); });
        $('#filter_group').select2().on('change', function() { initDataTablebenefit_table(); });
        $('#business_id, #group').select2({dropdownParent: $('#ajaxModel')});
    });
</script>
@endsection
