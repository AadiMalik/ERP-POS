@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        {{ __('delivery-zones.title') }}
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
                @canAccess('delivery-zone.create')
                <a href="{{ url('admin/delivery-zone/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    {{ __('common.add_new') }}
                </a>
                @endcanAccess
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
                            <option value="">{{ __('delivery-zones.all_branches') }}</option>
                            @if (RoleNames::SUPERADMIN != getRoleName())
                            @foreach ($branches as $item)
                            <option value="{{ $item->branch_id }}">{{ isset($item->code) ? $item->code : '' }}
                                {{ $item->name ?? '' }}
                            </option>
                            @endforeach
                            @endif
                        </select>
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
                <table id="delivery_zone_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('delivery-zones.min_km') }}</th>
                            <th>{{ __('delivery-zones.max_km') }}</th>
                            <th>{{ __('delivery-zones.fee') }}</th>
                            <th>{{ __('common.branch') }}</th>
                            <th>{{ __('common.status') }}</th>
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
    $__i18nDeliveryZones = [
        'all_branches' => __('delivery-zones.all_branches'),
        'select_branch' => __('delivery-zones.select_branch'),
    ];
@endphp
<script>
    window.i18n_delivery_zones = @json($__i18nDeliveryZones);
</script>
@include('admin.partials.datatable', [
'columns' => "
{data:'name',name:'name'},
{data:'min_km',name:'min_km'},
{data:'max_km',name:'max_km'},
{data:'fee',name:'fee'},
{data:'branch',name:'branch',sortable:false},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'delivery-zone/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'delivery_zone_table',
'variable' => 'delivery_zone_table',
'datefilter' => false,
'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val()",
])

<script>
    $(document).ready(function() {
        $('#business_id').select2();
        $('#branch_id').select2();
    });
    $('#search_btn').click(function() {
        initDataTabledelivery_zone_table();
    });
    $('#business_id').change(function() {
        let business_id = $(this).val();
        if (!business_id) {
            $('#branch_id').html('<option value="">' + (window.i18n_delivery_zones?.all_branches || '--All Branches--') + '</option>');
            return;
        }
        ajaxRequest({
                url: url_local + '/admin/branch/by-business/' + business_id,
                data: {}
            })
            .then((response) => {
                let data = response.Data;
                let options = '<option value="">' + (window.i18n_delivery_zones?.select_branch || '--Select Branch--') + '</option>';
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
            buttonClass: ".statusDeliveryZone",
            url: url_local + "/admin/delivery-zone/change-status",
            tableCallback: function() {
                initDataTabledelivery_zone_table();
            }
        });
    //delete
    deleteRecord({
        buttonClass: "#deleteDeliveryZone",
        url: url_local + "/admin/delivery-zone",

        tableCallback: function() {
            initDataTabledelivery_zone_table();
        }
    });
</script>
@endsection
