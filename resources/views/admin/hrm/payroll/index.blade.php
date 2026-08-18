@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Payroll</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div></div>
            @can('payroll.create')
            <a href="{{ url('admin/payroll/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i>
                Generate Payroll
            </a>
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive p-4">
                <table id="payroll_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Action</th>
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
{data:'period',name:'period',sortable:false},
{data:'total_amount',name:'total_amount'},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'payroll/data',
'buttons' => false,
'pageLength' => 12,
'class' => 'payroll_table',
'variable' => 'payroll_table',
'datefilter' => false,
'params' => "",
])
@endsection
