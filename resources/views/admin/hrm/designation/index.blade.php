@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_designations.title') }}</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    {{ __('common.filters') }}
                </button>
            </div>
            <div class="d-flex gap-2">
                @include('admin.partials.import-export-buttons', [
                    'importExportModule' => 'designation',
                    'importExportLabel' => __('hrm_designations.title'),
                    'importExportRefreshFn' => 'initDataTabledesignation_table',
                ])
                @can('designation.create')
                <a href="{{ url('admin/designation/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    {{ __('common.add_new') }}
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select id="department_id" class="form-select">
                            <option value="">--All Departments--</option>
                            @foreach ($departments as $item)
                            <option value="{{ $item->department_id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="designation_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('common.code') }}</th>
                            <th>{{ __('common.name') }}</th>
                            <th>Department</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
        @include('admin.partials.import-export-modal')
    </div>
</div>
@endsection
@section('js')
@include('admin.partials.datatable', [
'columns' => "
{data:'code',name:'code'},
{data:'name',name:'name'},
{data:'department',name:'department',sortable:false},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'designation/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'designation_table',
'variable' => 'designation_table',
'datefilter' => false,
'params' => "department_id:$('#department_id').val()",
])
<script>
    $(document).ready(function() {
        $('#department_id').select2();
    });
    $('#search_btn').click(function() {
        initDataTabledesignation_table();
    });
    deleteRecord({
        buttonClass: "#deleteDesignation",
        url: url_local + "/admin/designation",
        tableCallback: function() {
            initDataTabledesignation_table();
        }
    });
</script>
@endsection
