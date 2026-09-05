@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        {{ __('warehouses.title') }}
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
                    'importExportModule' => 'warehouse',
                    'importExportLabel' => __('warehouses.title'),
                    'importExportRefreshFn' => 'initDataTablewarehouse_table',
                    'importExportExportParamsSelector' => '#business_id',
                ])
                <a href="{{ url('admin/warehouse/create') }}" class="btn btn-primary rounded-pill">
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
                        <label class="form-label">{{ __('common.branch') }}</label>
                        <select id="branch_id" class="form-select">
                            <option value="">{{ __('warehouses.all_branches') }}</option>
                            @if (RoleNames::SUPERADMIN != getRoleName())
                            @foreach ($branches as $item)
                            <option value="{{ $item->branch_id }}">{{ isset($item->code) ? $item->code : '' }}
                                {{ $item->name ?? '' }}
                            </option>
                            @endforeach
                            @endif
                        </select>
                    </div>
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
                <table id="warehouse_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('common.code') }}</th>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.phone') }}</th>
                            <th>{{ __('common.address') }}</th>
                            <th>{{ __('common.branch') }}</th>
                            <th>{{ __('common.business') }}</th>
                            <th>{{ __('common.status') }}</th>
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
@php
    $__i18nWarehouses = [
        'all_branches' => __('warehouses.all_branches'),
        'select_branch' => __('warehouses.select_branch'),
    ];
@endphp
<script>
    window.i18n_warehouses = @json($__i18nWarehouses);
</script>
@include('admin.partials.datatable', [
'columns' => "
{data:'code',name:'code'},
{data:'name',name:'name'},
{data:'phone',name:'phone'},
{data:'address',name:'address'},
{data:'branch',name:'branch',sortable:false},
{data:'business',name:'business',sortable:false},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'warehouse/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'warehouse_table',
'variable' => 'warehouse_table',
'datefilter' => true,
'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val()",
])

<script>
    $(document).ready(function() {
        $('#business_id').select2();
        $('#branch_id').select2();
    });
    $('#search_btn').click(function() {
        initDataTablewarehouse_table();
    });
    $('#business_id').change(function() {
        let business_id = $(this).val();
        if (!business_id) {
            $('#branch_id').html('<option value="">' + (window.i18n_warehouses?.all_branches || '--All Branches--') + '</option>');
            return;
        }
        ajaxRequest({
                url: url_local + '/admin/branch/by-business/' + business_id,
                data: {}
            })
            .then((response) => {
                let data = response.Data;
                let options = '<option value="">' + (window.i18n_warehouses?.select_branch || '--Select Branch--') + '</option>';
                $.each(data, function(index, item) {
                    options += `<option value="${item.branch_id}">
                                        ${item.name}
                                    </option>
                                    `;
                });
                $('#branch_id').html(options);
            })
            .catch((err) => {
                errorMessage(err.Message);
            });
    });
    //status
    updateStatus({
            buttonClass: ".statusWarehouse",
            url: url_local + "/admin/warehouse/change-status",
            tableCallback: function() {
                initDataTablewarehouse_table();
            }
        });
    //delete
    deleteRecord({
        buttonClass: "#deleteWarehouse",
        url: url_local + "/admin/warehouse",

        tableCallback: function() {
            initDataTablewarehouse_table();
        }
    });
</script>
@endsection
