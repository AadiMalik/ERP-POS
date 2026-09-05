@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Payment Gateways</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    Filters
                </button>
            </div>
            <a href="{{ url('admin/payment-gateway/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i>
                Add Gateway
            </a>
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    @if (RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-3">
                        <label class="form-label">Business</label>
                        <select id="business_id" class="form-select">
                            <option value="">--All Businesses--</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="payment_gateway_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th>Display Name</th>
                            <th>Mode</th>
                            <th>Platforms</th>
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
{data:'provider',name:'provider',sortable:false},
{data:'display_name',name:'display_name'},
{data:'mode',name:'mode',sortable:false},
{data:'platforms',name:'platforms',sortable:false},
{data:'status',name:'status',sortable:false},
{data:'action',name:'action',sortable:false}",
'route' => 'payment-gateway/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'payment_gateway_table',
'variable' => 'payment_gateway_table',
'params' => "business_id:$('#business_id').val()",
])

<script>
    $(document).ready(function() {
        $('#business_id').select2();
    });
    $('#search_btn').click(function() {
        initDataTablepayment_gateway_table();
    });

    updateStatus({
        buttonClass: ".statusPaymentGateway",
        url: url_local + "/admin/payment-gateway/change-status",
        tableCallback: function() {
            initDataTablepayment_gateway_table();
        }
    });

    deleteRecord({
        buttonClass: "#deletePaymentGateway",
        url: url_local + "/admin/payment-gateway",
        tableCallback: function() {
            initDataTablepayment_gateway_table();
        }
    });

    $(document).on('click', '#testPaymentGateway', function() {
        let id = $(this).data('id');
        ajaxRequest({
                url: url_local + '/admin/payment-gateway/test-connection/' + id,
                method: 'POST',
                data: {}
            })
            .then((response) => {
                successMessage(response.Message);
            })
            .catch((err) => {
                errorMessage(err.Message);
            });
    });
</script>
@endsection
