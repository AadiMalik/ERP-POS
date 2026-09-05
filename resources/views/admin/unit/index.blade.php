@extends('layouts.app')
@section('css')
@endsection
@section('content')
<!-- ========== table components start ========== -->
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('units.title') }}</h4>

    <!-- Basic Bootstrap Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    {{ __('common.filters') }}
                </button>

            </div>
            <a href="javascript:void(0)" id="createNewUnit" class="btn rounded-pill btn-primary">
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
                <table id="unit_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('units.col_name') }}</th>
                            <th>{{ __('units.col_status') }}</th>
                            <th>{{ __('units.col_action') }}</th>
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
    @include('admin/unit/model/create')
</div>
<!-- ========== table components end ========== -->
@endsection
@section('js')
@php
    $__i18nUnits = [
        'create_title' => __('units.create_title'),
        'edit_title' => __('units.edit_title'),
        'view_title' => __('units.view_title'),
        'please_enter_name' => __('common.please_enter_name'),
    ];
@endphp
<script>
    window.i18n_units = @json($__i18nUnits);
</script>
<script src="{{ asset('public/assets/js/admin/unit.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'name' , name: 'name'},
{data: 'status' , name: 'status' , 'sortable': false , searchable: false},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'unit/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'unit_table',
'variable' => 'unit_table',
'datefilter' => true,
])

<script>
    $('#search_btn').click(function() {
        initDataTableunit_table();
    });
</script>
@endsection
