@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('period_closing_rules.title') }}</h4>

        <div class="card">
            <div class="card-body">
                <p class="text-muted">
                    {{ __('period_closing_rules.intro') }}
                </p>
                <form id="period_closing_rule_form">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="check_unposted_journal_entries"
                            id="check_unposted_journal_entries" value="1"
                            {{ $rule->check_unposted_journal_entries ? 'checked' : '' }}>
                        <label class="form-check-label" for="check_unposted_journal_entries">
                            {{ __('period_closing_rules.check_unposted_journals') }}
                        </label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="check_pending_purchase_returns"
                            id="check_pending_purchase_returns" value="1"
                            {{ $rule->check_pending_purchase_returns ? 'checked' : '' }}>
                        <label class="form-check-label" for="check_pending_purchase_returns">
                            {{ __('period_closing_rules.check_purchase_returns') }}
                        </label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="check_pending_leave_requests"
                            id="check_pending_leave_requests" value="1"
                            {{ $rule->check_pending_leave_requests ? 'checked' : '' }}>
                        <label class="form-check-label" for="check_pending_leave_requests">
                            {{ __('period_closing_rules.check_leave_requests') }}
                        </label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="check_pending_employee_advances"
                            id="check_pending_employee_advances" value="1"
                            {{ $rule->check_pending_employee_advances ? 'checked' : '' }}>
                        <label class="form-check-label" for="check_pending_employee_advances">
                            {{ __('period_closing_rules.check_employee_advances') }}
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="check_pending_employee_exits"
                            id="check_pending_employee_exits" value="1"
                            {{ $rule->check_pending_employee_exits ? 'checked' : '' }}>
                        <label class="form-check-label" for="check_pending_employee_exits">
                            {{ __('period_closing_rules.check_employee_exits') }}
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary" id="saveBtn">{{ __('common.save_changes') }}</button>
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
                errorMessage(err.Message || window.i18n_period_closing_rules.could_not_save);
            });
        });
    </script>
@endsection
