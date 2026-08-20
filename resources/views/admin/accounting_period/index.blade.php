@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Accounting Periods</h4>

        <div class="card">
            <div class="card-header">
                <div>Advanced Accounting Mode &mdash; open/close/reopen periods manually. Reopening and overriding a
                    blocked close both require a reason, which is recorded to the Activity Log.</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="accounting_period_table">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Closed Automatically</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Close modal -->
        <div class="modal fade" id="closeModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Close Period</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="close_period_id">
                        <div id="closeIssuesBox" class="alert alert-warning d-none"></div>
                        <div class="mb-3">
                            <label class="form-label">Reason (required if overriding pending items)</label>
                            <textarea class="form-control" id="close_reason"></textarea>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="close_override">
                            <label class="form-check-label" for="close_override">
                                Close anyway, even though items are pending
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirmClose">Close Period</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reopen modal -->
        <div class="modal fade" id="reopenModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reopen Period</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="reopen_period_id">
                        <div class="mb-3">
                            <label class="form-label">Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reopen_reason" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirmReopen">Reopen Period</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function statusBadge(status) {
            switch (status) {
                case 'open': return '<span class="badge bg-success">Open</span>';
                case 'closed': return '<span class="badge bg-secondary">Closed</span>';
                case 'pending_close': return '<span class="badge bg-warning">Pending Close</span>';
                default: return '<span class="badge bg-info">Upcoming</span>';
            }
        }

        function loadAccountingPeriods() {
            ajaxRequest({
                url: url_local + "/admin/accounting-period/data",
                method: "POST",
            }).then(function(response) {
                let rows = response.Data || [];
                let html = "";
                rows.forEach(function(row) {
                    let actions = "";
                    if (row.status === 'upcoming') {
                        actions += `<button class="btn btn-sm btn-outline-primary openPeriod" data-id="${row.accounting_period_id}">Open</button> `;
                    }
                    if (row.status === 'open' || row.status === 'pending_close') {
                        actions += `<button class="btn btn-sm btn-outline-danger closePeriodBtn" data-id="${row.accounting_period_id}">Close</button> `;
                    }
                    if (row.status === 'closed') {
                        actions += `<button class="btn btn-sm btn-outline-warning reopenPeriodBtn" data-id="${row.accounting_period_id}">Reopen</button> `;
                    }
                    actions += `<button class="btn btn-sm btn-outline-secondary viewIssuesBtn" data-id="${row.accounting_period_id}">Issues</button>`;

                    html += `<tr>
                        <td>${row.name}</td>
                        <td>${row.start_date}</td>
                        <td>${row.end_date}</td>
                        <td>${statusBadge(row.status)}</td>
                        <td>${row.closed_automatically ? '<i class="fa fa-check"></i>' : ''}</td>
                        <td>${actions}</td>
                    </tr>`;
                });
                $("#accounting_period_table tbody").html(html || '<tr><td colspan="6" class="text-center">No accounting periods yet.</td></tr>');
            });
        }

        $(document).ready(function() {
            loadAccountingPeriods();

            $("body").on("click", ".openPeriod", function() {
                let id = $(this).data("id");
                ajaxRequest({
                    url: url_local + "/admin/accounting-period/" + id + "/open",
                    method: "POST",
                }).then(function(response) {
                    successMessage(response.Message);
                    loadAccountingPeriods();
                }).catch(function(err) {
                    errorMessage(err.Message || "Could not open period");
                });
            });

            $("body").on("click", ".closePeriodBtn", function() {
                $("#close_period_id").val($(this).data("id"));
                $("#close_reason").val("");
                $("#close_override").prop("checked", false);
                $("#closeIssuesBox").addClass("d-none").html("");
                $("#closeModal").modal("show");
            });

            $("#confirmClose").on("click", function() {
                let id = $("#close_period_id").val();
                ajaxRequest({
                    url: url_local + "/admin/accounting-period/" + id + "/close",
                    method: "POST",
                    data: {
                        reason: $("#close_reason").val(),
                        override: $("#close_override").is(":checked") ? 1 : 0,
                    },
                }).then(function(response) {
                    let data = response.Data;
                    if (data && data.result === 'blocked') {
                        $("#closeIssuesBox").removeClass("d-none").html(
                            "This period has pending items and was not closed. Resolve them, or tick \"Close anyway\" with a reason to override."
                        );
                        loadAccountingPeriods();
                        return;
                    }
                    successMessage(response.Message);
                    $("#closeModal").modal("hide");
                    loadAccountingPeriods();
                }).catch(function(err) {
                    errorMessage(err.Message || "Could not close period");
                });
            });

            $("body").on("click", ".reopenPeriodBtn", function() {
                $("#reopen_period_id").val($(this).data("id"));
                $("#reopen_reason").val("");
                $("#reopenModal").modal("show");
            });

            $("#confirmReopen").on("click", function() {
                let id = $("#reopen_period_id").val();
                let reason = $("#reopen_reason").val();
                if (!reason || !reason.trim()) {
                    errorMessage("A reason is required to reopen this period.");
                    return;
                }
                ajaxRequest({
                    url: url_local + "/admin/accounting-period/" + id + "/reopen",
                    method: "POST",
                    data: { reason: reason },
                }).then(function(response) {
                    successMessage(response.Message);
                    $("#reopenModal").modal("hide");
                    loadAccountingPeriods();
                }).catch(function(err) {
                    errorMessage(err.Message || "Could not reopen period");
                });
            });

            $("body").on("click", ".viewIssuesBtn", function() {
                let id = $(this).data("id");
                ajaxRequest({
                    url: url_local + "/admin/accounting-period/" + id + "/issues",
                    method: "GET",
                }).then(function(response) {
                    let issues = response.Data || [];
                    if (!issues.length) {
                        successMessage("No pending issues for this period.");
                        return;
                    }
                    let list = issues.map(function(i) { return "- " + i.summary; }).join("\n");
                    alert("Pending items:\n\n" + list);
                });
            });
        });
    </script>
@endsection
