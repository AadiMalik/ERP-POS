@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_salary_components.title') }}</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div></div>
            @can('salary-component.create')
            <a href="{{ url('admin/salary-component/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i>
                {{ __('common.add_new') }}
            </a>
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive p-4">
                <table id="salary_component_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('common.code') }}</th>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.type') }}</th>
                            <th>{{ __('hrm_salary_components.calculation') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
@include('admin.partials.datatable', [
'columns' => "
{data:'code',name:'code'},
{data:'name',name:'name'},
{data:'type',name:'type'},
{data:'calculation_type',name:'calculation_type',sortable:false},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'salary-component/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'salary_component_table',
'variable' => 'salary_component_table',
'datefilter' => false,
'params' => "",
])
<script>
    deleteRecord({
        buttonClass: "#deleteSalaryComponent",
        url: url_local + "/admin/salary-component",
        tableCallback: function() {
            initDataTablesalary_component_table();
        }
    });
</script>
@endsection
