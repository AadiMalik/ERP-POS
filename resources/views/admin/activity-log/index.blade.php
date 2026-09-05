@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('activity_log.title') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
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
                            <label class="form-label">{{ __('common.module') }}</label>
                            <select id="module" class="form-select">
                                <option value="">{{ __('common.all_modules') }}</option>
                                @foreach ($modules as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.action') }}</label>
                            <select id="action" class="form-select">
                                <option value="">{{ __('activity_log.all_actions') }}</option>
                                @foreach ($actions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($causers->isNotEmpty())
                            <div class="col-md-3">
                                <label class="form-label">{{ __('activity_log.user') }}</label>
                                <select id="causer_id" class="form-select">
                                    <option value="">{{ __('activity_log.all_users') }}</option>
                                    @foreach ($causers as $item)
                                        <option value="{{ $item->user_id }}">{{ $item->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Record ID</label>
                            <input type="text" id="record_id" class="form-control" placeholder="e.g. order/JV ID">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.date') }}</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="activity_log_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Module</th>
                                <th>{{ __('common.action') }}</th>
                                <th>{{ __('common.description') }}</th>
                                <th>User</th>
                                <th>Branch</th>
                                <th>{{ __('common.business') }}</th>
                                <th>IP Address</th>
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
                        {data:'date_created',name:'date_created'},
                        {data:'module',name:'module'},
                        {data:'action',name:'action',sortable:false},
                        {data:'description',name:'description',sortable:false},
                        {data:'causer',name:'causer',sortable:false},
                        {data:'branch',name:'branch',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'ip_address',name:'ip_address',sortable:false}",
        'route' => 'activity-log/data',
        'buttons' => false,
        'pageLength' => 25,
        'class' => 'activity_log_table',
        'variable' => 'activity_log_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),module:$('#module').val(),action:$('#action').val(),causer_id:$('#causer_id').val(),record_id:$('#record_id').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#module').select2();
            $('#action').select2();
            $('#causer_id').select2();
        });
        $('#search_btn').click(function() {
            initDataTableactivity_log_table();
        });
        $('#reset_filter').click(function() {
            $('#business_id, #module, #action, #causer_id').val('').trigger('change');
            $('#record_id').val('');
            initDataTableactivity_log_table();
        });
    </script>
@endsection
