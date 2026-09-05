@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Intro Pages / SEO</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap align-items-center"></div>
            <a href="javascript:void(0)" id="createIntroPage" class="btn rounded-pill btn-primary"><i class="fa fa-plus me-1"></i> {{ __('common.add_new') }}</a>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-2">
                <table id="intro_pages_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('common.title') }}</th>
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
    @include('admin.intro.pages.model.create')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/intro_pages.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data:'title',name:'title'},{data:'slug',name:'slug'},{data:'status',name:'status'},{data:'action',name:'action',sortable:false,searchable:false},
",
'route' => 'pages/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'intro_pages_table',
'variable' => 'intro_pages_table',
])

@endsection