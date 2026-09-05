@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            {{ __('service_sales.title') }}
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <a href="{{ url('admin/service-sale/create') }}" class="btn btn-primary rounded-pill">
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
                            <label class="form-label">{{ __('common.customer') }}</label>
                            <select id="customer_id" class="form-select">
                                <option value="">{{ __('common.all_customers') }}</option>
                                @foreach ($customers as $item)
                                    <option value="{{ $item->user_id }}">{{ $item->code ?? '' }}
                                        {{ $item->user->name ?? '' }}
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
                    <table id="service_sale_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('service_sales.service_sale_no') }}</th>
                                <th>{{ __('common.sale_date') }}</th>
                                <th>{{ __('common.customer') }}</th>
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
                        {data:'service_sale_no',name:'service_sale_no'},
                        {data:'service_sale_date',name:'service_sale_date'},
                        {data:'customer',name:'customer',sortable:false},
                        {data:'total_items',name:'total_items',sortable:false},
                        {data:'total',name:'total'},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'service-sale/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'service_sale_table',
        'variable' => 'service_sale_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),customer_id:$('#customer_id').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#customer_id').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTableservice_sale_table();
        });
        //status
        $(document).on('change', '.change-status', function() {

            let service_sale_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            $.ajax({
                url: url_local + "/admin/service-sale/change-status",
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    service_sale_id: service_sale_id,
                    status: status
                },
                success: function(response) {
                    successMessage(response.Message);
                    initDataTableservice_sale_table();
                },
                error: function(error) {
                    errorMessage(error.responseJSON?.Message || 'Something went wrong.');
                    initDataTableservice_sale_table();
                    select.val(select.data('old'));
                }
            });
        });
        //delete
        deleteRecord({
            buttonClass: "#deleteServiceSale",
            url: url_local + "/admin/service-sale",
            tableCallback: function() {
                initDataTableservice_sale_table();
            }
        });
    </script>
@endsection
