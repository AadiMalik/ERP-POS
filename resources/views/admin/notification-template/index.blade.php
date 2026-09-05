@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('notification_templates.title') }}</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i> {{ __('common.filters') }}
                </button>
            </div>
            @canAccess('notification-template.create')
            <a href="{{ url('admin/notification-template/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i> Add New
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
                            <option value="{{ $item->business_id }}">{{ $item->code }} {{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.status') }}</label>
                        <select id="status" class="form-select">
                            <option value="">--All--</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
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
                <table id="notification_template_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.title') }}</th>
                            <th>{{ __('common.business') }}</th>
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
@include('admin.partials.datatable', [
'columns' => "
{data:'name',name:'name'},
{data:'title',name:'title'},
{data:'business',name:'business',sortable:false},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'notification-template/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'notification_template_table',
'variable' => 'notification_template_table',
'datefilter' => true,
'params' => "business_id:$('#business_id').val(),status:$('#status').val()",
])
<script>
    $('#search_btn').click(function() { initDataTablenotification_template_table(); });
    updateStatus({
        buttonClass: ".statusNotificationTemplate",
        url: url_local + "/admin/notification-template/change-status",
        tableCallback: function() { initDataTablenotification_template_table(); }
    });
    deleteRecord({
        buttonClass: "#deleteNotificationTemplate",
        url: url_local + "/admin/notification-template",
        tableCallback: function() { initDataTablenotification_template_table(); }
    });
</script>
@endsection
