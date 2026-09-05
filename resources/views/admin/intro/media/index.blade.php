@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Intro Media</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <input type="text" id="filter_collection" class="form-control" style="width:200px" placeholder="Filter collection">
            <a href="javascript:void(0)" id="createIntroMedia" class="btn rounded-pill btn-primary"><i class="fa fa-plus me-1"></i>Upload</a>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-2">
                <table id="intro_media_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Preview</th>
                            <th>Original Name</th>
                            <th>Collection</th>
                            <th>Mime</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @include('admin.intro.media.model.create')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/intro_media.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data:'preview',name:'preview',sortable:false,searchable:false},
{data:'original_name',name:'original_name'},
{data:'collection',name:'collection'},
{data:'mime_type',name:'mime_type'},
{data:'action',name:'action',sortable:false,searchable:false},
",
'route' => 'media/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'intro_media_table',
'variable' => 'intro_media_table',
'params' => "collection:$('#filter_collection').val()",
])
<script>$(function(){ $('#filter_collection').on('change keyup', function(){ initDataTableintro_media_table(); }); });</script>
@endsection