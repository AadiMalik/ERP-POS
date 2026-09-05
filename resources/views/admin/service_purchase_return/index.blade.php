@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            {{ __('service_purchase_returns.title') }}
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <a href="{{ url('admin/service-purchase-return/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    {{ __('common.add_new') }}
                </a>
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
                                        <option value="{{ $item->business_id }}">{{ $item->code ?? '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.supplier') }}</label>
                            <select id="supplier_id" class="form-select">
                                <option value="">{{ __('common.all_suppliers') }}</option>
                                @foreach ($suppliers as $item)
                                    <option value="{{ $item->supplier_id }}">{{ $item->code ?? '' }}
                                        {{ $item->name ?? '' }}
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
                                Search
                            </button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="service_purchase_return_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('common.return_no') }}</th>
                                <th>{{ __('common.return_date') }}</th>
                                <th>{{ __('service_purchases.service_purchase_no') }}</th>
                                <th>{{ __('common.supplier') }}</th>
                                <th>{{ __('common.items') }}</th>
                                <th>{{ __('common.total') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('common.business') }}</th>
                                <th>{{ __('common.action') }}</th>
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
                        {data:'service_purchase_return_no',name:'service_purchase_return_no'},
                        {data:'service_purchase_return_date',name:'service_purchase_return_date'},
                        {data:'source_no',name:'source_no',sortable:false},
                        {data:'supplier',name:'supplier',sortable:false},
                        {data:'total_items',name:'total_items',sortable:false},
                        {data:'total',name:'total'},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'service-purchase-return/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'service_purchase_return_table',
        'variable' => 'service_purchase_return_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),supplier_id:$('#supplier_id').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#supplier_id').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTableservice_purchase_return_table();
        });
        //status
        $(document).on('change', '.change-status', function() {

            let service_purchase_return_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            $.ajax({
                url: url_local + "/admin/service-purchase-return/change-status",
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    service_purchase_return_id: service_purchase_return_id,
                    status: status
                },
                success: function(response) {
                    successMessage(response.Message);
                    initDataTableservice_purchase_return_table();
                },
                error: function(error) {
                    errorMessage(error.responseJSON?.Message || 'Something went wrong.');
                    initDataTableservice_purchase_return_table();
                    select.val(select.data('old'));
                }
            });
        });
        //delete
        deleteRecord({
            buttonClass: "#deleteServicePurchaseReturn",
            url: url_local + "/admin/service-purchase-return",
            tableCallback: function() {
                initDataTableservice_purchase_return_table();
            }
        });
    </script>
@endsection
