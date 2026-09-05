@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            {{ __('suppliers.title') }}
        </h4>
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
                        'importExportModule' => 'supplier',
                        'importExportLabel' => __('suppliers.title'),
                        'importExportRefreshFn' => 'initDataTablesupplier_table',
                        'importExportExportParamsSelector' => '#business_id',
                    ])
                    <a href="{{ url('admin/supplier/create') }}" class="btn btn-primary rounded-pill">
                        <i class="fa fa-plus"></i>
                        {{ __('common.add_new') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">{{ __('common.business') }}</label>
                                <select id="business_id" class="form-select">
                                    <option value="">{{ __('common.all_businesses') }}</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
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
                <div class="table-responsive p-4">
                    <table id="supplier_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('common.code') }}</th>
                                <th>{{ __('common.name') }}</th>
                                <th>{{ __('suppliers.company') }}</th>
                                <th>{{ __('common.email') }}</th>
                                <th>{{ __('common.phone') }}</th>
                                <th>{{ __('common.address') }}</th>
                                <th>{{ __('common.balance') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('common.business') }}</th>
                                <th>{{ __('common.action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        @include('admin.partials.import-export-modal')
    </div>
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
    {data:'code',name:'code'},
    {data:'name',name:'name'},
    {data:'company_name',name:'company_name'},
    {data:'email',name:'email'},
    {data:'phone',name:'phone'},
    {data:'address',name:'address'},
    {data:'balance',name:'balance'},
    {data:'status',name:'status',sortable:false},
    {data:'business',name:'business',sortable:false},
    {data:'action',name:'action',sortable:false}",
        'route' => 'supplier/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'supplier_table',
        'variable' => 'supplier_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
        });
        $('#search_btn').click(function() {
            initDataTablesupplier_table();
        });
        //status
        updateStatus({
            buttonClass: ".statusSupplier",
            url: url_local + "/admin/supplier/change-status",
            tableCallback: function() {
                initDataTablesupplier_table();
            }
        });
        //delete
        deleteRecord({
            buttonClass: "#deleteSupplier",
            url: url_local + "/admin/supplier",

            tableCallback: function() {
                initDataTablesupplier_table();
            }
        });
    </script>
@endsection
