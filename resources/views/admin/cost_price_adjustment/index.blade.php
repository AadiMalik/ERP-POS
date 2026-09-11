@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            {{ __('cost_price_adjustment.title') }}
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                @canAccess('cost-price-adjustment.create')
                    <a href="{{ url('admin/cost-price-adjustment/create') }}" class="btn btn-primary rounded-pill">
                        <i class="fa fa-plus"></i>
                        {{ __('common.add_new') }}
                    </a>
                @endcanAccess
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
                                    <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select id="status" class="form-select">
                                <option value="">{{ __('common.all_statuses') }}</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label ?? '' }}</option>
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
                    <table id="cost_price_adjustment_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('cost_price_adjustment.reference_no') }}</th>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('common.product') }}</th>
                                <th>{{ __('common.variation') }}</th>
                                <th>{{ __('common.warehouse') }}</th>
                                <th>{{ __('cost_price_adjustment.previous_cost_price') }}</th>
                                <th>{{ __('cost_price_adjustment.new_cost_price') }}</th>
                                <th>{{ __('cost_price_adjustment.total_adjustment_amount') }}</th>
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
    @php
        $__i18nCpa = [
            'something_went_wrong' => __('common.something_went_wrong'),
            'approve_title' => __('cost_price_adjustment.approve_title'),
            'approve_text' => __('cost_price_adjustment.approve_text'),
            'yes_approve' => __('cost_price_adjustment.yes_approve'),
        ];
    @endphp
    <script>
        window.i18n_cost_price_adjustment = @json($__i18nCpa);
    </script>
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'reference_no',name:'reference_no'},
                        {data:'adjustment_date',name:'adjustment_date'},
                        {data:'product',name:'product',sortable:false},
                        {data:'variation',name:'variation',sortable:false},
                        {data:'warehouse',name:'warehouse',sortable:false},
                        {data:'previous_cost_price',name:'previous_cost_price',sortable:false},
                        {data:'new_cost_price',name:'new_cost_price',sortable:false},
                        {data:'total_adjustment_amount',name:'total_adjustment_amount',sortable:false},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'cost-price-adjustment/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'cost_price_adjustment_table',
        'variable' => 'cost_price_adjustment_table',
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
            initDataTablecost_price_adjustment_table();
        });

        function submitCpaStatus(cost_price_adjustment_id, status, select) {
            $.ajax({
                url: url_local + "/admin/cost-price-adjustment/change-status",
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    cost_price_adjustment_id: cost_price_adjustment_id,
                    status: status,
                },
                success: function(response) {
                    successMessage(response.Message);
                    initDataTablecost_price_adjustment_table();
                },
                error: function(xhr) {
                    errorMessage(xhr.responseJSON?.Message || window.i18n_cost_price_adjustment?.something_went_wrong || 'Something went wrong.');
                    initDataTablecost_price_adjustment_table();
                    select.val(select.data('old'));
                }
            });
        }

        $(document).on('change', '.change-status', function() {
            let cost_price_adjustment_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            if (status === 'approved') {
                Swal.fire({
                    title: window.i18n_cost_price_adjustment?.approve_title || 'Approve this cost price adjustment?',
                    text: window.i18n_cost_price_adjustment?.approve_text || 'Inventory valuation will be updated and a Journal Voucher will be posted once approved.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: window.i18n_cost_price_adjustment?.yes_approve || 'Yes, approve'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitCpaStatus(cost_price_adjustment_id, status, select);
                    } else {
                        select.val(select.data('old'));
                    }
                });
                return;
            }

            submitCpaStatus(cost_price_adjustment_id, status, select);
        });

        deleteRecord({
            buttonClass: "#deleteCostPriceAdjustment",
            url: url_local + "/admin/cost-price-adjustment",
            tableCallback: function() {
                initDataTablecost_price_adjustment_table();
            }
        });
    </script>
@endsection
