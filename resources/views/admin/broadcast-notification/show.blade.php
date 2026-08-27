@php
use App\Enums\BroadcastNotificationStatus;
use App\Enums\BroadcastRecipientStatus;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">Broadcast: {{ $campaign->title }}</h4>
        <div class="d-flex gap-2">
            <a href="{{ url('admin/broadcast-notification') }}" class="btn btn-outline-secondary">Back</a>
            @canAccess('broadcast-notification.start')
            @if (in_array($campaign->status, BroadcastNotificationStatus::startable(), true))
                <button type="button" class="btn btn-success" id="btnStart"
                    @if(!$hasFirebase) disabled title="Configure Firebase first" @endif>
                    <i class="fa fa-play"></i> Start
                </button>
            @endif
            @endcanAccess
            @canAccess('broadcast-notification.cancel')
            @if (in_array($campaign->status, BroadcastNotificationStatus::cancellable(), true)
                && $campaign->status !== BroadcastNotificationStatus::DRAFT)
                <button type="button" class="btn btn-warning" id="btnCancel">
                    <i class="fa fa-stop"></i> Cancel
                </button>
            @endif
            @endcanAccess
            @canAccess('broadcast-notification.resend')
            @if ($campaign->failed_count > 0 && in_array($campaign->status, [
                BroadcastNotificationStatus::COMPLETED,
                BroadcastNotificationStatus::FAILED,
                BroadcastNotificationStatus::CANCELLED,
            ], true))
                <button type="button" class="btn btn-primary" id="btnResend">
                    <i class="fa fa-redo"></i> Resend Failed
                </button>
            @endif
            @endcanAccess
        </div>
    </div>

    @if (!$hasFirebase)
        <div class="alert alert-danger">
            Firebase configuration is not configured for this business.
            Please configure Firebase under
            <a href="{{ url('admin/setting') }}#firebase">Settings → Firebase</a>
            before starting the notification.
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row mb-4">
        <div class="col-md-2"><div class="card"><div class="card-body text-center"><div class="text-muted">Total</div><h4>{{ $campaign->total_count }}</h4></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body text-center"><div class="text-muted">Success</div><h4 class="text-success">{{ $campaign->success_count }}</h4></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body text-center"><div class="text-muted">Failed</div><h4 class="text-danger">{{ $campaign->failed_count }}</h4></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body text-center"><div class="text-muted">Pending</div><h4 class="text-warning">{{ $campaign->pending_count }}</h4></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body text-center"><div class="text-muted">Cancelled</div><h4>{{ $campaign->cancelled_count }}</h4></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body text-center"><div class="text-muted">Status</div><h5 class="mb-0">{{ BroadcastNotificationStatus::labels()[$campaign->status] ?? $campaign->status }}</h5></div></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6"><strong>Business:</strong> {{ $campaign->business?->name ?? '-' }}</div>
                <div class="col-md-6"><strong>Template:</strong> {{ $campaign->template?->name ?? '-' }}</div>
                <div class="col-md-6"><strong>Created by:</strong> {{ $campaign->createdBy?->name ?? '-' }}</div>
                <div class="col-md-6"><strong>Created:</strong> {{ $campaign->date_created }}</div>
                <div class="col-md-6"><strong>Started:</strong> {{ $campaign->started_at ?? '-' }}</div>
                <div class="col-md-6"><strong>Completed:</strong> {{ $campaign->completed_at ?? '-' }}</div>
                <div class="col-md-12 mt-3"><strong>Body:</strong><br>{{ $campaign->body }}</div>
                @if ($campaign->image)
                <div class="col-md-12 mt-2"><strong>Image:</strong> {{ $campaign->image }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recipients</h5>
            <select id="recipient_status" class="form-select w-auto">
                <option value="">All</option>
                @foreach (BroadcastRecipientStatus::labels() as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="card-body table-responsive">
            <table id="broadcast_recipients_table" class="table datatables">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Device</th>
                        <th>Token</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Sent At</th>
                        <th>Error</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection
@section('js')
@include('admin.partials.datatable', [
'columns' => "
{data:'user_name',name:'user_name',sortable:false},
{data:'user_email',name:'user_email',sortable:false},
{data:'device_info',name:'device_info',sortable:false},
{data:'token_preview',name:'token_preview',sortable:false},
{data:'status_badge',name:'status',sortable:false},
{data:'attempts',name:'attempts'},
{data:'sent_at_fmt',name:'sent_at',sortable:false},
{data:'error_message',name:'error_message'}",
'route' => 'broadcast-notification/' . $campaign->broadcast_notification_id . '/recipients/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'broadcast_recipients_table',
'variable' => 'broadcast_recipients_table',
'datefilter' => false,
'params' => "status:$('#recipient_status').val()",
])
<script>
    $('#recipient_status').on('change', function() {
        initDataTablebroadcast_recipients_table();
    });

    function postAction(url, msg) {
        if (!confirm(msg)) return;
        ajaxRequest({ url: url, method: 'POST', data: {} })
            .then(function(res) {
                successMessage(res.Message || 'Done');
                window.location.reload();
            })
            .catch(function(err) { errorMessage(err.Message || 'Action failed'); });
    }

    $('#btnStart').on('click', function() {
        postAction('{{ route('broadcast-notification.start', $campaign->broadcast_notification_id) }}',
            'Start this campaign now?');
    });
    $('#btnCancel').on('click', function() {
        postAction('{{ route('broadcast-notification.cancel', $campaign->broadcast_notification_id) }}',
            'Cancel remaining pending recipients?');
    });
    $('#btnResend').on('click', function() {
        postAction('{{ route('broadcast-notification.resend-failed', $campaign->broadcast_notification_id) }}',
            'Resend failed recipients with active tokens?');
    });
</script>
@endsection
