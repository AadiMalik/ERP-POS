@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        {{ __('banks.title') }}
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
                <a href="{{ url('admin/bank/create') }}" class="btn btn-primary rounded-pill">
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
                            <option value="">{{ __('banks.all_branches') }}</option>
                            @if (RoleNames::SUPERADMIN != getRoleName())
                            @foreach ($branches as $item)
                            <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
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
                <table id="bank_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.code') }}</th>
                            <th>{{ __('banks.select_account') }}</th>
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
    $__i18nBanks = [
        'all_branches' => __('banks.all_branches'),
    ];
@endphp
<script>
    window.i18n_banks = @json($__i18nBanks);
</script>
@include('admin.partials.datatable', [
'columns' => "
{data:'name',name:'name'},
{data:'code',name:'code'},
{data:'account',name:'account',sortable:false},
{data:'branch',name:'branch',sortable:false},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'bank/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'bank_table',
'variable' => 'bank_table',
'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val()",
])

<script>
    $(document).ready(function() {
        $('#business_id').select2();
        $('#branch_id').select2();
    });
    $('#search_btn').click(function() {
        initDataTablebank_table();
    });
    //status
    updateStatus({
        buttonClass: ".statusBank",
        url: url_local + "/admin/bank/change-status",
        tableCallback: function() {
            initDataTablebank_table();
        }
    });
    //delete
    deleteRecord({
        buttonClass: "#deleteBank",
        url: url_local + "/admin/bank",
        tableCallback: function() {
            initDataTablebank_table();
        }
    });
</script>
@endsection
