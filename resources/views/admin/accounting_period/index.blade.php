@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('accounting_periods.title') }}</h4>

        <div class="card">
            <div class="card-header">
                <div>{{ __('accounting_periods.intro') }}</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="accounting_period_table">
                        <thead>
                            <tr>
                                <th>{{ __('common.period') }}</th>
                                <th>{{ __('common.start_date') }}</th>
                                <th>{{ __('common.end_date') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('accounting_periods.closed_automatically') }}</th>
                                <th>{{ __('common.action') }}</th>
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
                        <h5 class="modal-title">{{ __('accounting_periods.close_period') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="close_period_id">
                        <div id="closeIssuesBox" class="alert alert-warning d-none"></div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('accounting_periods.reason_override') }}</label>
                            <textarea class="form-control" id="close_reason"></textarea>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="close_override">
                            <label class="form-check-label" for="close_override">
                                {{ __('accounting_periods.close_anyway') }}
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="button" class="btn btn-primary" id="confirmClose">{{ __('accounting_periods.close_period') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reopen modal -->
        <div class="modal fade" id="reopenModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('accounting_periods.reopen_period') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="reopen_period_id">
                        <div class="mb-3">
                            <label class="form-label">{{ __('common.reason') }} <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reopen_reason" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="button" class="btn btn-primary" id="confirmReopen">{{ __('accounting_periods.reopen_period') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @php
        $__i18nAp = [
            'open' => __('common.open'),
            'closed' => __('common.closed'),
            'pending_close' => __('accounting_periods.pending_close'),
            'upcoming' => __('common.upcoming'),
            'open_btn' => __('accounting_periods.open_btn'),
            'close_btn' => __('accounting_periods.close_btn'),
            'reopen_btn' => __('accounting_periods.reopen_btn'),
            'issues_btn' => __('accounting_periods.issues_btn'),
            'no_periods' => __('accounting_periods.no_periods'),
            'blocked_message' => __('accounting_periods.blocked_message'),
            'reason_required_reopen' => __('accounting_periods.reason_required_reopen'),
            'could_not_open' => __('accounting_periods.could_not_open'),
            'could_not_close' => __('accounting_periods.could_not_close'),
            'could_not_reopen' => __('accounting_periods.could_not_reopen'),
            'no_pending_issues' => __('accounting_periods.no_pending_issues'),
            'pending_items' => __('accounting_periods.pending_items'),
            'yes' => __('common.yes'),
            'no' => __('common.no'),
        ];
    @endphp
    <script>window.i18n_accounting_periods = @json($__i18nAp);</script>
    <script>
        function statusBadge(status) {
            switch (status) {
                case 'open': return '<span class="badge bg-success">' + window.i18n_accounting_periods.open + '</span>';
                case 'closed': return '<span class="badge bg-secondary">' + window.i18n_accounting_periods.closed + '</span>';
                case 'pending_close': return '<span class="badge bg-warning">' + window.i18n_accounting_periods.pending_close + '</span>';
                default: return '<span class="badge bg-info">' + window.i18n_accounting_periods.upcoming + '</span>';
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
                        actions += `<button class="btn btn-sm btn-outline-primary openPeriod" data-id="${row.accounting_period_id}">${window.i18n_accounting_periods.open_btn}</button> `;
                    }
                    if (row.status === 'open' || row.status === 'pending_close') {
                        actions += `<button class="btn btn-sm btn-outline-danger closePeriodBtn" data-id="${row.accounting_period_id}">${window.i18n_accounting_periods.close_btn}</button> `;
                    }
                    if (row.status === 'closed') {
                        actions += `<button class="btn btn-sm btn-outline-warning reopenPeriodBtn" data-id="${row.accounting_period_id}">${window.i18n_accounting_periods.reopen_btn}</button> `;
                    }
                    actions += `<button class="btn btn-sm btn-outline-secondary viewIssuesBtn" data-id="${row.accounting_period_id}">${window.i18n_accounting_periods.issues_btn}</button>`;

                    html += `<tr>
                        <td>${row.name}</td>
                        <td>${row.start_date}</td>
                        <td>${row.end_date}</td>
                        <td>${statusBadge(row.status)}</td>
                        <td>${row.closed_automatically ? '<i class="fa fa-check"></i>' : ''}</td>
                        <td>${actions}</td>
                    </tr>`;
                });
                $("#accounting_period_table tbody").html(html || '<tr><td colspan="6" class="text-center">' + window.i18n_accounting_periods.no_periods + '</td></tr>');
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
                    errorMessage(err.Message || window.i18n_accounting_periods.could_not_open);
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
                            window.i18n_accounting_periods.blocked_message
                        );
                        loadAccountingPeriods();
                        return;
                    }
                    successMessage(response.Message);
                    $("#closeModal").modal("hide");
                    loadAccountingPeriods();
                }).catch(function(err) {
                    errorMessage(err.Message || window.i18n_accounting_periods.could_not_close);
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
                    errorMessage(window.i18n_accounting_periods.reason_required_reopen);
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
                    errorMessage(err.Message || window.i18n_accounting_periods.could_not_reopen);
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
                        successMessage(window.i18n_accounting_periods.no_pending_issues);
                        return;
                    }
                    let list = issues.map(function(i) { return "- " + i.summary; }).join("\n");
                    alert(window.i18n_accounting_periods.pending_items + "\n\n" + list);
                });
            });
        });
    </script>
@endsection
