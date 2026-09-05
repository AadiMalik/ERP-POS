@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            {{ __('opening_stock.title') }}
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
                        'importExportModule' => 'opening-stock',
                        'importExportLabel' => __('opening_stock.title'),
                        'importExportRefreshFn' => 'initDataTableopening_stock_table',
                        'importExportExportParamsSelector' => '#business_id',
                    ])
                    <a href="{{ url('admin/opening-stock/create') }}" class="btn btn-primary rounded-pill">
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
                            <label class="form-label">{{ __('common.warehouse') }}</label>
                            <select id="warehouse_id" class="form-select">
                                <option value="">{{ __('common.all_warehouses') }}</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select id="status" class="form-select">
                                <option value="">{{ __('common.all_statuses') }}</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label ?? '' }}
                                    </option>
                                @endforeach
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
                    <table id="opening_stock_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('opening_stock.opening_stock_no') }}</th>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('common.warehouse') }}</th>
                                <th>{{ __('common.products') }}</th>
                                <th>{{ __('common.total_value') }}</th>
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
    @php
        $__i18nOpeningStock = [
            'something_went_wrong' => __('common.something_went_wrong'),
        ];
    @endphp
    <script>
        window.i18n_opening_stock = @json($__i18nOpeningStock);
    </script>
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'opening_stock_no',name:'opening_stock_no'},
                        {data:'opening_stock_date',name:'opening_stock_date'},
                        {data:'warehouse',name:'warehouse',sortable:false},
                        {data:'total_products',name:'total_products',sortable:false},
                        {data:'total_value',name:'total_value'},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'opening-stock/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'opening_stock_table',
        'variable' => 'opening_stock_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),warehouse_id:$('#warehouse_id').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#warehouse_id').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTableopening_stock_table();
        });
        //status
        $(document).on('change', '.change-status', function() {

            let opening_stock_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            $.ajax({
                url: url_local + "/admin/opening-stock/change-status", // route
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    opening_stock_id: opening_stock_id,
                    status: status
                },
                success: function(response) {

                    successMessage(response.Message);
                    initDataTableopening_stock_table();
                },
                error: function() {

                    errorMessage(error.Message || window.i18n_opening_stock?.something_went_wrong || 'Something went wrong.');
                    initDataTableopening_stock_table();
                    // Previous value restore
                    select.val(select.data('old'));
                }
            });

        });
        //delete
        deleteRecord({
            buttonClass: "#deleteOpeningStock",
            url: url_local + "/admin/opening-stock",

            tableCallback: function() {
                initDataTableopening_stock_table();
            }
        });
    </script>
@endsection
