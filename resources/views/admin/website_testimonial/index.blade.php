@php
use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Testimonials</h4>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            @if (RoleNames::SUPERADMIN == getRoleName())
            <div class="col-md-3">
                <select id="filter_business_id" class="form-select">
                    <option value="">--All Businesses--</option>
                    @foreach ($business as $item)
                    <option value="{{ $item->business_id }}">{{ $item->name ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <div></div>
            @endif
            <a href="javascript:void(0)" id="createNewTestimonial" class="btn rounded-pill btn-primary">
                <i class="icon-base fa fa-plus mr-5"></i>Add New</a>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-4">
                <table id="testimonial_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Author</th>
                            <th>Quote</th>
                            <th>Rating</th>
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
    @include('admin.website_testimonial.model.create')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/website_testimonial.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'preview' , name: 'preview', 'sortable': false , searchable: false},
{data: 'author_name' , name: 'author_name'},
{data: 'quote' , name: 'quote'},
{data: 'rating' , name: 'rating'},
{data: 'sort_order' , name: 'sort_order'},
{data: 'status' , name: 'status', 'sortable': false , searchable: false},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'website-testimonial/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'testimonial_table',
'variable' => 'testimonial_table',
'params' => "business_id:$('#filter_business_id').val()",
])
<script>
    $(document).ready(function() { $('#filter_business_id').select2().on('change', function() { initDataTabletestimonial_table(); }); $('#business_id').select2({dropdownParent: $('#ajaxModel')}); });
</script>
@endsection
