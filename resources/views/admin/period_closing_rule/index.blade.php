@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Period Closing Rules</h4>

        <div class="card">
            <div class="card-body">
                <p class="text-muted">
                    Choose which checks must pass before a period (automatic or manual) is allowed
                    to close. If any enabled check finds pending items, the period is left open and
                    the specific items are shown on the Accounting Periods screen.
                </p>
                <form id="period_closing_rule_form">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="check_unposted_journal_entries"
                            id="check_unposted_journal_entries" value="1"
                            {{ $rule->check_unposted_journal_entries ? 'checked' : '' }}>
                        <label class="form-check-label" for="check_unposted_journal_entries">
                            Block closing if there are unposted (pending) journal entries in the period
                        </label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="check_pending_purchase_returns"
                            id="check_pending_purchase_returns" value="1"
                            {{ $rule->check_pending_purchase_returns ? 'checked' : '' }}>
                        <label class="form-check-label" for="check_pending_purchase_returns">
                            Block closing if there are Purchase Returns pending approval
                        </label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="check_pending_leave_requests"
                            id="check_pending_leave_requests" value="1"
                            {{ $rule->check_pending_leave_requests ? 'checked' : '' }}>
                        <label class="form-check-label" for="check_pending_leave_requests">
                            Block closing if there are Leave Requests pending approval
                        </label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="check_pending_employee_advances"
                            id="check_pending_employee_advances" value="1"
                            {{ $rule->check_pending_employee_advances ? 'checked' : '' }}>
                        <label class="form-check-label" for="check_pending_employee_advances">
                            Block closing if there are Employee Advances pending approval
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="check_pending_employee_exits"
                            id="check_pending_employee_exits" value="1"
                            {{ $rule->check_pending_employee_exits ? 'checked' : '' }}>
                        <label class="form-check-label" for="check_pending_employee_exits">
                            Block closing if there are Resignations/Terminations pending approval
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $("#period_closing_rule_form").on("submit", function(e) {
            e.preventDefault();

            let data = {};
            $(this).find('input[type=checkbox]').each(function() {
                data[$(this).attr('name')] = $(this).is(':checked') ? 1 : 0;
            });

            ajaxRequest({
                url: url_local + "/admin/period-closing-rule",
                method: "POST",
                data: data,
            }).then(function(response) {
                successMessage(response.Message);
            }).catch(function(err) {
                errorMessage(err.Message || "Could not save closing rules");
            });
        });
    </script>
@endsection
