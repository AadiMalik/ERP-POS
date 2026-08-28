@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Intro Blog Posts</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap">
                <select id="filter_status" class="form-select" style="width:160px">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="scheduled">Scheduled</option>
                </select>
                <select id="filter_category" class="form-select" style="width:200px">
                    <option value="">All Categories</option>
                    @foreach($categories as $c)
                    <option value="{{ $c->intro_blog_category_id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <a href="javascript:void(0)" id="createIntroBlog" class="btn rounded-pill btn-primary"><i class="fa fa-plus me-1"></i>Add New</a>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-2">
                <table id="intro_blogs_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Published</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @include('admin.intro.blogs.model.create')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/intro_blogs.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data:'title',name:'title'},
{data:'category',name:'category',sortable:false,searchable:false},
{data:'status_badge',name:'status',sortable:false,searchable:false},
{data:'published_at',name:'published_at'},
{data:'action',name:'action',sortable:false,searchable:false},
",
'route' => 'blogs/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'intro_blogs_table',
'variable' => 'intro_blogs_table',
'params' => "status_filter:$('#filter_status').val(),category_id:$('#filter_category').val()",
])
<script>
$(function(){
    $('#tag_ids').select2({dropdownParent:$('#ajaxModel'), width:'100%'});
    $('#filter_status,#filter_category').on('change', function(){ initDataTableintro_blogs_table(); });
});
</script>
@endsection