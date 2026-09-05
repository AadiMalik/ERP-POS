@extends('layouts.app')
@section('css')
@endsection
@section('content')
<!-- ========== table components start ========== -->
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('discounts.pos_title') }}</h4>

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
                    'importExportModule' => 'discount',
                    'importExportLabel' => __('discounts.title'),
                    'importExportRefreshFn' => 'initDataTablepos_discount_table',
                ])
                <a href="javascript:void(0)" id="createNewDiscount" class="btn rounded-pill btn-primary">
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
                <table id="pos_discount_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.type') }}</th>
                            <th>{{ __('common.value') }}</th>
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
    @include('admin.discount.model.create')
    @include('admin.partials.import-export-modal')
</div>
<!-- ========== table components end ========== -->
@endsection
@section('js')
@php
    $__i18nDiscounts = [
        'create_new' => __('discounts.create_new'),
        'edit_heading' => __('discounts.edit_heading'),
        'please_enter_name' => __('discounts.please_enter_name'),
        'please_enter_value' => __('discounts.please_enter_value'),
    ];
@endphp
<script>window.i18n_discounts = @json($__i18nDiscounts);</script>
<script src="{{ asset('public/assets/js/admin/discount.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'name' , name: 'name'},
{data: 'type' , name: 'type', 'sortable': false , searchable: false},
{data: 'value' , name: 'value', 'sortable': false , searchable: false},
{data: 'status' , name: 'status' , 'sortable': false , searchable: false},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'discount/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'pos_discount_table',
'variable' => 'pos_discount_table',
'datefilter' => true,
])

<script>
    $(document).ready(function() {
        $('#business_id').select2({
            dropdownParent: $('#ajaxModel')
        });
    });
    $('#search_btn').click(function() {
        initDataTablepos_discount_table();
    });
</script>
@endsection
