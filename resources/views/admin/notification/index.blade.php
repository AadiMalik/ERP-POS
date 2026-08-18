@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4 d-flex justify-content-between align-items-center">
            Notifications
            <button type="button" class="btn btn-outline-primary btn-sm" id="markAllReadBtn">
                <i class="fa fa-check-double"></i> Mark All as Read
            </button>
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select id="type" class="form-select">
                                <option value="">--All Types--</option>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select id="is_read" class="form-select">
                                <option value="">--All--</option>
                                <option value="0">Unread</option>
                                <option value="1">Read</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
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
                    <table id="notification_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Status</th>
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
                        {data:'message',name:'message',sortable:false},
                        {data:'type',name:'type',sortable:false},
                        {data:'date_created',name:'date_created'},
                        {data:'status',name:'status',sortable:false}",
        'route' => 'notifications/data',
        'buttons' => false,
        'pageLength' => 25,
        'class' => 'notification_table',
        'variable' => 'notification_table',
        'datefilter' => true,
        'params' => "type:$('#type').val(),is_read:$('#is_read').val()",
        'rowCallback' => "function(row, data) {
            if (!data.is_read) { $(row).addClass('table-warning'); }
            $(row).css('cursor', 'pointer');
            $(row).off('click').on('click', function() {
                goToNotification(data.notification_recipient_id, data.url);
            });
        }",
    ])

    <script>
        $(document).ready(function() {
            $('#type').select2();
            $('#is_read').select2();
        });
        $('#search_btn').click(function() {
            initDataTablenotification_table();
        });
        $('#reset_filter').click(function() {
            $('#type, #is_read').val('').trigger('change');
            initDataTablenotification_table();
        });

        function goToNotification(notification_recipient_id, url) {
            ajaxRequest({
                url: "{{ url('admin/notifications') }}/" + notification_recipient_id + "/read",
                method: 'POST',
            }).finally(function() {
                if (url) {
                    window.location.href = url;
                } else {
                    initDataTablenotification_table();
                }
            });
        }

        $('#markAllReadBtn').click(function() {
            ajaxRequest({
                url: "{{ route('notifications.mark-all-read') }}",
                method: 'POST',
            }).then(function(res) {
                successMessage(res.Message);
                initDataTablenotification_table();
                if (typeof refreshNotificationBell === 'function') {
                    refreshNotificationBell();
                }
            });
        });
    </script>
@endsection
