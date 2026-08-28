@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Intro Business Registrations</h4>
    <div class="card">
        <div class="card-header d-flex gap-2 flex-wrap">
            <select id="filter_status" class="form-select" style="width:180px">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="under_review">Under Review</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="activated">Activated</option>
            </select>
            <input type="text" id="filter_search" class="form-control" style="width:220px" placeholder="Search name / email">
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-2">
                <table id="intro_registrations_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Business</th>
                            <th>Owner</th>
                            <th>Email</th>
                            <th>Package</th>
                            <th>Cycle</th>
                            <th>Sub Status</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @include('admin.intro.business_registrations.model.view')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/intro_business_registrations.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data:'business_name',name:'business_name'},
{data:'owner_name',name:'owner_name'},
{data:'owner_email',name:'owner_email'},
{data:'package_name',name:'package_name',sortable:false,searchable:false},
{data:'billing_cycle',name:'billing_cycle'},
{data:'subscription_status',name:'subscription_status',sortable:false,searchable:false},
{data:'status_badge',name:'status',sortable:false,searchable:false},
{data:'action',name:'action',sortable:false,searchable:false},
",
'route' => 'business-registrations/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'intro_registrations_table',
'variable' => 'intro_registrations_table',
'params' => "status_filter:$('#filter_status').val(),search:$('#filter_search').val()",
])
<script>
$(function(){
    $('#filter_status').on('change', function(){ initDataTableintro_registrations_table(); });
    let t; $('#filter_search').on('keyup', function(){ clearTimeout(t); t=setTimeout(function(){ initDataTableintro_registrations_table(); }, 400); });
});
</script>
@endsection