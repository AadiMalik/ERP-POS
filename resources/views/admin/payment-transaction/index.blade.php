@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Payment Gateway Transactions</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                <i class="fa fa-filter"></i>
                Filters
            </button>
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    @if (RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-2">
                        <label class="form-label">Business</label>
                        <select id="business_id" class="form-select">
                            <option value="">--All--</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <label class="form-label">Provider</label>
                        <select id="provider_code" class="form-select">
                            <option value="">--All--</option>
                            @foreach ($providers as $code => $meta)
                            <option value="{{ $code }}">{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Environment</label>
                        <select id="environment" class="form-select">
                            <option value="">--All--</option>
                            <option value="sandbox">Sandbox</option>
                            <option value="live">Live</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select id="status" class="form-select">
                            <option value="">--All--</option>
                            @foreach (['initiated','pending','processing','authorized','paid','failed','cancelled','expired','refunded','partially_refunded','disputed','unknown'] as $s)
                            <option value="{{ $s }}">{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date</label>
                        @include('admin.partials.date_filter')
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="payment_transaction_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Gateway</th>
                            <th>Environment</th>
                            <th>Amount</th>
                            <th>Currency</th>
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
{data:'order_number',name:'order_number',sortable:false},
{data:'gateway',name:'gateway',sortable:false},
{data:'environment',name:'environment'},
{data:'amount',name:'amount'},
{data:'currency',name:'currency'},
{data:'status_badge',name:'status',sortable:false},
{data:'action',name:'action',sortable:false}",
'route' => 'payment-transaction/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'payment_transaction_table',
'variable' => 'payment_transaction_table',
'datefilter' => true,
'params' => "business_id:$('#business_id').val(),provider_code:$('#provider_code').val(),environment:$('#environment').val(),status:$('#status').val()",
])

<script>
    $(document).ready(function() {
        @if (RoleNames::SUPERADMIN == getRoleName())
        $('#business_id').select2();
        @endif
    });
    $('#search_btn').click(function() {
        initDataTablepayment_transaction_table();
    });

    $(document).on('click', '#refundPaymentTransaction', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Refund this payment?',
            text: 'This calls the gateway\'s real refund API.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, refund it',
        }).then((result) => {
            if (!result.isConfirmed) return;
            ajaxRequest({
                    url: url_local + '/admin/payment-transaction/refund/' + id,
                    method: 'POST',
                    data: {}
                })
                .then((response) => {
                    successMessage(response.Message);
                    initDataTablepayment_transaction_table();
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });
        });
    });
</script>
@endsection
