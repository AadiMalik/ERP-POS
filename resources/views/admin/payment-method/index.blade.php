@extends('layouts.app')
@section('css')
@endsection
@section('content')
<!-- ========== table components start ========== -->
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('payment_methods.pos_title') }}</h4>

    <!-- Basic Bootstrap Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    {{ __('common.filters') }}
                </button>

            </div>
            <a href="javascript:void(0)" id="createNewPaymentMethod" class="btn rounded-pill btn-primary">
                <i class="icon-base fa fa-plus mr-5"></i>{{ __('common.add_new') }}</a>
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.date') }}</label>
                        @include('admin.partials.date_filter')
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">
                            {{ __('common.search') }}
                        </button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                            {{ __('common.reset') }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive text-nowrap p-4">
                <table id="pos_payment_method_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.code') }}</th>
                            <th>{{ __('common.account') }}</th>
                            <th>{{ __('payment_methods.default') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                        <!-- end table row-->
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                <!-- end table -->
            </div>
        </div>
    </div>
    @include('admin/payment-method/model/create')
</div>
<!-- ========== table components end ========== -->
@endsection
@section('js')
@php
    $__i18nPaymentMethods = [
        'create_new' => __('payment_methods.create_new'),
        'edit_heading' => __('payment_methods.edit_heading'),
        'please_enter_name' => __('payment_methods.please_enter_name'),
        'please_enter_code' => __('payment_methods.please_enter_code'),
        'please_select_account' => __('payment_methods.please_select_account'),
    ];
@endphp
<script>window.i18n_payment_methods = @json($__i18nPaymentMethods);</script>
<script src="{{ asset('public/assets/js/admin/payment-method.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'name' , name: 'name'},
{data: 'code' , name: 'code'},
{data: 'account' , name: 'account', 'sortable': false , searchable: false},
{data: 'is_default' , name: 'is_default', 'sortable': false , searchable: false},
{data: 'status' , name: 'status' , 'sortable': false , searchable: false},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'payment-method/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'pos_payment_method_table',
'variable' => 'pos_payment_method_table',
'datefilter' => true,
])

<script>
    $(document).ready(function() {
        $('#business_id').select2({
            dropdownParent: $('#ajaxModel')
        });
        $('#account_id').select2({
            dropdownParent: $('#ajaxModel')
        });
    });
    $('#search_btn').click(function() {
        initDataTablepos_payment_method_table();
    });
</script>
@endsection
