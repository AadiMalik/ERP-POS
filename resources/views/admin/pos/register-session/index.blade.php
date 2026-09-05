@extends('layouts.app')
@section('css')
@endsection
@section('content')
<!-- ========== table components start ========== -->
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('pos.register_sessions_title') }}</h4>

    <!-- Basic Bootstrap Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    {{ __('common.filters') }}
                </button>
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
                <table id="pos_register_session_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('common.register') }}</th>
                            <th>{{ __('common.branch') }}</th>
                            <th>{{ __('common.cashier') }}</th>
                            <th>{{ __('pos.opened') }}</th>
                            <th>{{ __('pos.closed_at') }}</th>
                            <th>{{ __('pos.opening_cash') }}</th>
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
</div>
<!-- ========== table components end ========== -->
@endsection
@section('js')
@php
    $__i18nPos = array_merge(trans('pos'), [
        'cancel' => __('common.cancel'),
    ]);
@endphp
<script>
    window.i18n_pos = @json($__i18nPos);
</script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'register' , name: 'register', 'sortable': false , searchable: false},
{data: 'branch' , name: 'branch', 'sortable': false , searchable: false},
{data: 'cashier' , name: 'cashier', 'sortable': false , searchable: false},
{data: 'opening_datetime' , name: 'opening_datetime'},
{data: 'closing_datetime' , name: 'closing_datetime', 'sortable': false , searchable: false},
{data: 'opening_cash' , name: 'opening_cash', 'sortable': false , searchable: false},
{data: 'status' , name: 'status' , 'sortable': false , searchable: false},
{data: 'action' , name: 'action', 'sortable': false , searchable: false},",
'route' => 'pos-register-session/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'pos_register_session_table',
'variable' => 'pos_register_session_table',
'datefilter' => true,
])

<script>
    $('#search_btn').click(function() {
        initDataTablepos_register_session_table();
    });
</script>
<script src="{{ asset('public/assets/js/admin/pos-register-session.js') }}"></script>
@endsection
