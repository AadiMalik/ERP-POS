@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_departments.title') }}</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div></div>
            <div class="d-flex gap-2">
                @include('admin.partials.import-export-buttons', [
                    'importExportModule' => 'department',
                    'importExportLabel' => __('hrm_departments.title'),
                    'importExportRefreshFn' => 'initDataTabledepartment_table',
                ])
                @can('department.create')
                <a href="{{ url('admin/department/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    {{ __('common.add_new') }}
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive p-4">
                <table id="department_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('common.code') }}</th>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.description') }}</th>
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
{data:'description',name:'description'},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'department/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'department_table',
'variable' => 'department_table',
'datefilter' => false,
'params' => "",
])
<script>
    deleteRecord({
        buttonClass: "#deleteDepartment",
        url: url_local + "/admin/department",
        tableCallback: function() {
            initDataTabledepartment_table();
        }
    });
</script>
@endsection
