@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Intro Blog Comments</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <select id="filter_status" class="form-select" style="width:180px">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="spam">Spam</option>
                <option value="hidden">Hidden</option>
            </select>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-2">
                <table id="intro_comments_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.email') }}</th>
                            <th>Blog</th>
                            <th>Comment</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/intro_blog_comments.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data:'name',name:'name'},
{data:'email',name:'email'},
{data:'blog_title',name:'blog_title',sortable:false,searchable:false},
{data:'comment',name:'comment'},
{data:'status_badge',name:'status',sortable:false,searchable:false},
{data:'action',name:'action',sortable:false,searchable:false},
",
'route' => 'blog-comments/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'intro_comments_table',
'variable' => 'intro_comments_table',
'params' => "status_filter:$('#filter_status').val()",
])
<script>$(function(){ $('#filter_status').on('change', function(){ initDataTableintro_comments_table(); }); });</script>
@endsection