@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Intro Contact Inquiries</h4>
    <div class="card">
        <div class="card-header">
            <select id="filter_status" class="form-select" style="width:180px">
                <option value="">All Status</option>
                <option value="new">New</option>
                <option value="read">Read</option>
                <option value="replied">Replied</option>
                <option value="closed">Closed</option>
            </select>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-2">
                <table id="intro_inquiries_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.email') }}</th>
                            <th>Subject</th>
                            <th>{{ __('common.business') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.date') }}</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @include('admin.intro.contact_inquiries.model.view')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/intro_contact_inquiries.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data:'name',name:'name'},
{data:'email',name:'email'},
{data:'subject',name:'subject'},
{data:'business_name',name:'business_name',sortable:false,searchable:false},
{data:'status_badge',name:'status',sortable:false,searchable:false},
{data:'date_created',name:'date_created'},
{data:'action',name:'action',sortable:false,searchable:false},
",
'route' => 'contact-inquiries/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'intro_inquiries_table',
'variable' => 'intro_inquiries_table',
'params' => "status_filter:$('#filter_status').val()",
])
<script>$(function(){ $('#filter_status').on('change', function(){ initDataTableintro_inquiries_table(); }); });</script>
@endsection