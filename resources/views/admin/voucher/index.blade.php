@extends('layouts.app')
@section('css')
@endsection
@section('content')
<!-- ========== table components start ========== -->
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('vouchers.pos_title') }}</h4>

    <!-- Basic Bootstrap Table -->
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
                    'importExportModule' => 'voucher',
                    'importExportLabel' => __('vouchers.title'),
                    'importExportRefreshFn' => 'initDataTablepos_voucher_table',
                ])
                <a href="javascript:void(0)" id="createNewVoucher" class="btn rounded-pill btn-primary">
                    <i class="icon-base fa fa-plus mr-5"></i>{{ __('common.add_new') }}</a>
            </div>
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
                <table id="pos_voucher_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.code') }}</th>
                            <th>{{ __('vouchers.rule') }}</th>
                            <th>{{ __('common.type') }}</th>
                            <th>{{ __('common.value') }}</th>
                            <th>{{ __('vouchers.usage') }}</th>
                            <th>{{ __('vouchers.valid_from') }}</th>
                            <th>{{ __('vouchers.valid_to') }}</th>
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
    @include('admin/voucher/model/create')
    @include('admin/voucher/model/history')
    @include('admin.partials.import-export-modal')
</div>
<!-- ========== table components end ========== -->
@endsection
@section('js')
@php
    $__i18nVouchers = [
        'create_new' => __('vouchers.create_new'),
        'edit_heading' => __('vouchers.edit_heading'),
        'walk_in' => __('vouchers.walk_in'),
        'unable_load_options' => __('vouchers.unable_load_options'),
        'unable_load_history' => __('vouchers.unable_load_history'),
        'please_enter_buy_get_qty' => __('vouchers.please_enter_buy_get_qty'),
        'please_enter_name' => __('common.please_enter_name'),
        'please_enter_code' => __('common.please_enter_code'),
        'please_enter_value' => __('common.please_enter_value'),
    ];
@endphp
<script>window.i18n_vouchers = @json($__i18nVouchers);</script>
<script src="{{ asset('public/assets/js/admin/voucher.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'name' , name: 'name'},
{data: 'code' , name: 'code'},
{data: 'rule' , name: 'rule', 'sortable': false , searchable: false},
{data: 'type' , name: 'type', 'sortable': false , searchable: false},
{data: 'value' , name: 'value', 'sortable': false , searchable: false},
{data: 'usage' , name: 'usage', 'sortable': false , searchable: false},
{data: 'valid_from' , name: 'valid_from'},
{data: 'valid_to' , name: 'valid_to'},
{data: 'status' , name: 'status' , 'sortable': false , searchable: false},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'voucher/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'pos_voucher_table',
'variable' => 'pos_voucher_table',
'datefilter' => true,
])

<script>
    $(document).ready(function() {
        $('#business_id').select2({
            dropdownParent: $('#ajaxModel')
        });
        $('.select2-multiple').select2({
            dropdownParent: $('#ajaxModel')
        });
    });
    $('#search_btn').click(function() {
        initDataTablepos_voucher_table();
    });
</script>
@endsection
