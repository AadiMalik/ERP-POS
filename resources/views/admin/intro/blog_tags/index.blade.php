@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Intro Blog Tags</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap align-items-center"></div>
            <a href="javascript:void(0)" id="createIntroBlogTag" class="btn rounded-pill btn-primary"><i class="fa fa-plus me-1"></i> {{ __('common.add_new') }}</a>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-2">
                <table id="intro_blog_tags_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.slug') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @include('admin.intro.blog_tags.model.create')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/intro_blog_tags.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data:'name',name:'name'},{data:'slug',name:'slug'},{data:'status',name:'status',sortable:false,searchable:false},{data:'action',name:'action',sortable:false,searchable:false},
",
'route' => 'blog-tags/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'intro_blog_tags_table',
'variable' => 'intro_blog_tags_table',
])

@endsection