@php
use App\Enums\RoleNames;
use App\Enums\BroadcastNotificationStatus;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Broadcast Notifications</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i> Filters
                </button>
            </div>
            @canAccess('broadcast-notification.create')
            <a href="{{ url('admin/broadcast-notification/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i> New Broadcast
            </a>
            @endcanAccess
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    @if (RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-3">
                        <label class="form-label">Business</label>
                        <select id="business_id" class="form-select">
                            <option value="">--All Businesses--</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}">{{ $item->code }} {{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="status" class="form-select">
                            <option value="">--All--</option>
                            @foreach (BroadcastNotificationStatus::labels() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        @include('admin.partials.date_filter')
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="broadcast_notification_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Template</th>
                            <th>Business</th>
                            <th>Total</th>
                            <th>Success</th>
                            <th>Failed</th>
                            <th>Pending</th>
                            <th>Cancelled</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created</th>
                            <th>Started</th>
                            <th>Completed</th>
                            <th>Action</th>
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
{data:'title',name:'title'},
{data:'template_name',name:'template_name',sortable:false},
{data:'business',name:'business',sortable:false},
{data:'total_count',name:'total_count'},
{data:'success_count',name:'success_count'},
{data:'failed_count',name:'failed_count'},
{data:'pending_count',name:'pending_count'},
{data:'cancelled_count',name:'cancelled_count'},
{data:'status_badge',name:'status',sortable:false},
{data:'created_by_name',name:'created_by_name',sortable:false},
{data:'date_created',name:'date_created'},
{data:'started_at',name:'started_at'},
{data:'completed_at',name:'completed_at'},
{data:'action',name:'action',sortable:false}",
'route' => 'broadcast-notification/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'broadcast_notification_table',
'variable' => 'broadcast_notification_table',
'datefilter' => true,
'params' => "business_id:$('#business_id').val(),status:$('#status').val()",
])
<script>
    $('#search_btn').click(function() { initDataTablebroadcast_notification_table(); });

    function postBroadcastAction(url, confirmText) {
        if (!confirm(confirmText)) return;
        ajaxRequest({ url: url, method: 'POST', data: {} })
            .then(function(res) {
                successMessage(res.Message || 'Done');
                initDataTablebroadcast_notification_table();
            })
            .catch(function(err) { errorMessage(err.Message || 'Action failed'); });
    }

    $(document).on('click', '.startBroadcast', function() {
        postBroadcastAction(url_local + '/admin/broadcast-notification/' + $(this).data('id') + '/start',
            'Start this broadcast campaign?');
    });
    $(document).on('click', '.cancelBroadcast', function() {
        postBroadcastAction(url_local + '/admin/broadcast-notification/' + $(this).data('id') + '/cancel',
            'Cancel this campaign? Pending recipients will not be sent.');
    });
    $(document).on('click', '.resendBroadcast', function() {
        postBroadcastAction(url_local + '/admin/broadcast-notification/' + $(this).data('id') + '/resend-failed',
            'Resend only to failed recipients with active tokens?');
    });
    deleteRecord({
        buttonClass: ".deleteBroadcast",
        url: url_local + "/admin/broadcast-notification",
        tableCallback: function() { initDataTablebroadcast_notification_table(); }
    });
</script>
@endsection
