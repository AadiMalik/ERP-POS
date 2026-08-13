@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Subscription Invoices</h4>
        <div class="card">
            <div class="table-responsive p-4">
                <table id="invoices_table" class="table datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Business</th>
                            <th>Package</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
        {data:'invoice_no',name:'invoice_no'},
        {data:'business',name:'business'},
        {data:'package',name:'package'},
        {data:'total',name:'total'},
        {data:'status',name:'status'},
        {data:'action',name:'action',sortable:false,searchable:false}",
        'route' => 'subscription-invoices/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'invoices_table',
        'variable' => 'invoices_table',
    ])
@endsection
