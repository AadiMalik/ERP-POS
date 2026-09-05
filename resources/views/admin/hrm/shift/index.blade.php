@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_shifts.title') }}</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div></div>
            <div class="d-flex gap-2">
                @include('admin.partials.import-export-buttons', [
                    'importExportModule' => 'shift',
                    'importExportLabel' => __('hrm_shifts.title'),
                    'importExportRefreshFn' => 'initDataTableshift_table',
                ])
                @can('shift.create')
                <a href="{{ url('admin/shift/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    {{ __('common.add_new') }}
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive p-4">
                <table id="shift_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>Timing</th>
                            <th>Working Days</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    @include('admin.partials.import-export-modal')
</div>
@endsection
@section('js')
@include('admin.partials.datatable', [
'columns' => "
{data:'name',name:'name'},
{data:'timing',name:'timing',sortable:false},
{data:'working_days',name:'working_days',sortable:false},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'shift/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'shift_table',
'variable' => 'shift_table',
'datefilter' => false,
'params' => "",
])
<script>
    deleteRecord({
        buttonClass: "#deleteShift",
        url: url_local + "/admin/shift",
        tableCallback: function() {
            initDataTableshift_table();
        }
    });
</script>
@endsection
