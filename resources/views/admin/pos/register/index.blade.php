@extends('layouts.app')
@section('css')
@endsection
@section('content')
<!-- ========== table components start ========== -->
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('pos.registers_title') }}</h4>

    <!-- Basic Bootstrap Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    {{ __('common.filters') }}
                </button>

            </div>
            <a href="javascript:void(0)" id="createNewPosRegister" class="btn rounded-pill btn-primary">
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
                <table id="pos_register_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.code') }}</th>
                            <th>{{ __('common.branch') }}</th>
                            <th>{{ __('common.warehouse') }}</th>
                            <th>{{ __('common.mode') }}</th>
                            <th>{{ __('common.assigned_user') }}</th>
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
    @include('admin/pos/register/model/create')
</div>
<!-- ========== table components end ========== -->
@endsection
@section('js')
@php
    $__i18nPos = [
        'create_register' => __('pos.create_register'),
        'edit_register' => __('pos.edit_register'),
        'please_enter_name' => __('common.please_enter_name'),
        'please_enter_code' => __('common.please_enter_code'),
        'please_select_branch' => __('common.please_select_branch'),
        'please_select_warehouse' => __('common.please_select_warehouse'),
    ];
@endphp
<script>window.i18n_pos = @json($__i18nPos);</script>
<script src="{{ asset('public/assets/js/admin/pos-register.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'name' , name: 'name'},
{data: 'code' , name: 'code'},
{data: 'branch' , name: 'branch', 'sortable': false , searchable: false},
{data: 'warehouse' , name: 'warehouse', 'sortable': false , searchable: false},
{data: 'mode' , name: 'mode', 'sortable': false , searchable: false},
{data: 'assigned_user' , name: 'assigned_user', 'sortable': false , searchable: false},
{data: 'status' , name: 'status' , 'sortable': false , searchable: false},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'pos-register/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'pos_register_table',
'variable' => 'pos_register_table',
'datefilter' => true,
])

<script>
    $(document).ready(function() {
        $('#business_id').select2({
            dropdownParent: $('#ajaxModel')
        });
        $('#branch_id').select2({
            dropdownParent: $('#ajaxModel')
        });
        $('#warehouse_id').select2({
            dropdownParent: $('#ajaxModel')
        });
        $('#assigned_user_id').select2({
            dropdownParent: $('#ajaxModel')
        });
    });
    $('#search_btn').click(function() {
        initDataTablepos_register_table();
    });
</script>
@endsection
