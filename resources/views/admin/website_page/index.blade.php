@php
use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Pages & Policies</h4>

    <div class="card">
        @if (RoleNames::SUPERADMIN == getRoleName())
        <div class="card-header">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Business</label>
                    <select id="filter_business_id" class="form-select">
                        @foreach ($business as $item)
                        <option value="{{ $item->business_id }}">{{ $item->name ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        @endif
        <div class="card-body">
            <div class="table-responsive text-nowrap p-4">
                <table id="website_page_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @include('admin.website_page.model.edit')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/website_page.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'title' , name: 'title'},
{data: 'slug' , name: 'slug'},
{data: 'status' , name: 'status', 'sortable': false , searchable: false},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'website-page/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'website_page_table',
'variable' => 'website_page_table',
'params' => "business_id:$('#filter_business_id').val()",
])
<script>
    $(document).ready(function() { $('#filter_business_id').select2().on('change', function() { initDataTablewebsite_page_table(); }); });
</script>
@endsection
