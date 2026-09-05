@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            {{ __('waste_damage_expiry.title') }}
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                @canAccess('waste-damage-expiry.create')
                    <a href="{{ url('admin/waste-damage-expiry/create') }}" class="btn btn-primary rounded-pill">
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
                    <table id="waste_damage_expiry_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('waste_damage_expiry.reference_no') }}</th>
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
    </div>
@endsection
@section('js')
    @php
        $__i18nWde = [
            'something_went_wrong' => __('common.something_went_wrong'),
            'approve_title' => __('waste_damage_expiry.approve_title'),
            'approve_text' => __('waste_damage_expiry.approve_text'),
            'yes_approve' => __('waste_damage_expiry.yes_approve'),
        ];
    @endphp
    <script>
        window.i18n_waste_damage_expiry = @json($__i18nWde);
    </script>
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'reference_no',name:'reference_no'},
                        {data:'transaction_date',name:'transaction_date'},
                        {data:'warehouse',name:'warehouse',sortable:false},
                        {data:'total_products',name:'total_products',sortable:false},
                        {data:'total_value',name:'total_value'},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'waste-damage-expiry/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'waste_damage_expiry_table',
        'variable' => 'waste_damage_expiry_table',
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
            initDataTablewaste_damage_expiry_table();
        });

        function submitWdeStatus(waste_damage_expiry_id, status, select) {
            $.ajax({
                url: url_local + "/admin/waste-damage-expiry/change-status",
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    waste_damage_expiry_id: waste_damage_expiry_id,
                    status: status,
                },
                success: function(response) {
                    successMessage(response.Message);
                    initDataTablewaste_damage_expiry_table();
                },
                error: function(xhr) {
                    errorMessage(xhr.responseJSON?.Message || window.i18n_waste_damage_expiry?.something_went_wrong || 'Something went wrong.');
                    initDataTablewaste_damage_expiry_table();
                    select.val(select.data('old'));
                }
            });
        }

        $(document).on('change', '.change-status', function() {
            let waste_damage_expiry_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            if (status === 'approved') {
                Swal.fire({
                    title: window.i18n_waste_damage_expiry?.approve_title || 'Approve this write-off?',
                    text: window.i18n_waste_damage_expiry?.approve_text || 'Stock will be permanently reduced from the selected warehouse once approved.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: window.i18n_waste_damage_expiry?.yes_approve || 'Yes, approve'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitWdeStatus(waste_damage_expiry_id, status, select);
                    } else {
                        select.val(select.data('old'));
                    }
                });
                return;
            }

            submitWdeStatus(waste_damage_expiry_id, status, select);
        });

        deleteRecord({
            buttonClass: "#deleteWasteDamageExpiry",
            url: url_local + "/admin/waste-damage-expiry",
            tableCallback: function() {
                initDataTablewaste_damage_expiry_table();
            }
        });
    </script>
@endsection
