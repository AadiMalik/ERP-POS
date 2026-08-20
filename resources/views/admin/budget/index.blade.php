@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Budgets</h4>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>Advanced Accounting Mode</div>
                @canAccess('budget.create')
                    <button type="button" class="btn btn-primary" id="addBudget"><i class="fa fa-plus"></i> Add Budget</button>
                @endcanAccess
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="budget_table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Fiscal Year</th>
                                <th>Granularity</th>
                                <th>Mode</th>
                                <th>Growth %</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add/Edit budget modal -->
        <div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modelHeading">Add Budget</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="budget_form">
                        <div class="modal-body">
                            <input type="hidden" name="budget_id" id="budget_id">
                            <div class="mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                                <select class="form-select" name="fiscal_year_id" id="fiscal_year_id" required>
                                    <option value="">--Select Fiscal Year--</option>
                                    @foreach ($fiscal_years as $fy)
                                        <option value="{{ $fy->fiscal_year_id }}">{{ $fy->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Granularity <span class="text-danger">*</span></label>
                                <select class="form-select" name="granularity" id="granularity" required>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Generation Mode <span class="text-danger">*</span></label>
                                <select class="form-select" name="generation_mode" id="generation_mode" required>
                                    <option value="manual">Manual</option>
                                    <option value="auto">Automatic (from last year's actuals)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Growth % (used when Automatic)</label>
                                <input type="number" step="0.01" class="form-control" name="growth_percent" id="growth_percent">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" id="saveBtn" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Budget lines modal -->
        <div class="modal fade" id="linesModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Budget Lines</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="lines_budget_id">
                        <form id="add_line_form" class="row g-2 mb-3">
                            <div class="col-md-4">
                                <select class="form-select" id="line_account_id" required>
                                    <option value="">--Account--</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->account_id }}">{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="line_branch_id">
                                    <option value="">--All Branches--</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->branch_id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" id="line_period_start" required title="Period start">
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" id="line_period_end" required title="Period end">
                            </div>
                            <div class="col-md-1">
                                <input type="number" step="0.01" class="form-control" id="line_amount" placeholder="Amt" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-primary">Add / Update Line</button>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th>Branch</th>
                                        <th>Period</th>
                                        <th>Budgeted Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="lines_tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function loadBudgets() {
            ajaxRequest({
                url: url_local + "/admin/budget/data",
                method: "POST",
            }).then(function(response) {
                let rows = response.Data || [];
                let html = "";
                rows.forEach(function(row) {
                    let statusBadge = row.status === 'active'
                        ? '<span class="badge bg-success">Active</span>'
                        : (row.status === 'archived' ? '<span class="badge bg-secondary">Archived</span>' : '<span class="badge bg-warning">Draft</span>');
                    html += `<tr>
                        <td>${row.name}</td>
                        <td>${row.fiscal_year ? row.fiscal_year.name : ''}</td>
                        <td>${row.granularity}</td>
                        <td>${row.generation_mode}</td>
                        <td>${row.growth_percent ?? ''}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-info generateBudgetBtn" data-id="${row.budget_id}">Generate</button>
                            <button class="btn btn-sm btn-outline-secondary linesBudgetBtn" data-id="${row.budget_id}">Lines</button>
                            <button class="btn btn-icon btn-outline-primary editBudget" data-id="${row.budget_id}"><i class="fa fa-pencil"></i></button>
                            <button class="btn btn-icon btn-outline-danger deleteBudget" data-id="${row.budget_id}"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>`;
                });
                $("#budget_table tbody").html(html || '<tr><td colspan="7" class="text-center">No budgets yet.</td></tr>');
            });
        }

        function loadLines(budgetId) {
            ajaxRequest({
                url: url_local + "/admin/budget/" + budgetId + "/edit",
                method: "GET",
            }).then(function(response) {
                let lines = (response.Data && response.Data.lines) || [];
                let html = "";
                lines.forEach(function(line) {
                    html += `<tr>
                        <td>${line.account ? (line.account.code + ' - ' + line.account.name) : ''}</td>
                        <td>${line.branch ? line.branch.name : 'All'}</td>
                        <td>${line.period_start} to ${line.period_end}</td>
                        <td>${parseFloat(line.budgeted_amount).toFixed(2)}</td>
                    </tr>`;
                });
                $("#lines_tbody").html(html || '<tr><td colspan="4" class="text-center">No lines yet.</td></tr>');
            });
        }

        $(document).ready(function() {
            loadBudgets();

            $("#addBudget").on("click", function() {
                $("#budget_form")[0].reset();
                $("#budget_id").val("");
                $("#modelHeading").text("Add Budget");
                $("#ajaxModel").modal("show");
            });

            editRecord({
                buttonClass: ".editBudget",
                url: url_local + "/admin/budget",
                onSuccess: function(response) {
                    let data = response.Data.budget || response.Data;
                    $("#budget_id").val(data.budget_id);
                    $("#name").val(data.name);
                    $("#fiscal_year_id").val(data.fiscal_year_id);
                    $("#granularity").val(data.granularity);
                    $("#generation_mode").val(data.generation_mode);
                    $("#growth_percent").val(data.growth_percent);
                    $("#modelHeading").text("Edit Budget");
                    $("#ajaxModel").modal("show");
                }
            });

            saveRecord({
                formId: "#budget_form",
                url: url_local + "/admin/budget",
                modalId: "#ajaxModel",
                tableCallback: loadBudgets,
            });

            deleteRecord({
                buttonClass: ".deleteBudget",
                url: url_local + "/admin/budget",
                tableCallback: loadBudgets,
            });

            $("body").on("click", ".generateBudgetBtn", function() {
                let id = $(this).data("id");
                ajaxRequest({
                    url: url_local + "/admin/budget/" + id + "/generate",
                    method: "POST",
                }).then(function(response) {
                    successMessage("Generated " + (response.Data.lines_written || 0) + " budget line(s).");
                    loadBudgets();
                }).catch(function(err) {
                    errorMessage(err.Message || "Could not generate budget");
                });
            });

            $("body").on("click", ".linesBudgetBtn", function() {
                let id = $(this).data("id");
                $("#lines_budget_id").val(id);
                loadLines(id);
                $("#linesModal").modal("show");
            });

            $("#add_line_form").on("submit", function(e) {
                e.preventDefault();
                let budgetId = $("#lines_budget_id").val();

                ajaxRequest({
                    url: url_local + "/admin/budget/" + budgetId + "/line",
                    method: "POST",
                    data: {
                        account_id: $("#line_account_id").val(),
                        branch_id: $("#line_branch_id").val(),
                        period_start: $("#line_period_start").val(),
                        period_end: $("#line_period_end").val(),
                        budgeted_amount: $("#line_amount").val(),
                    },
                }).then(function() {
                    successMessage("Budget line saved.");
                    loadLines(budgetId);
                }).catch(function(err) {
                    errorMessage(err.Message || "Could not save budget line");
                });
            });
        });
    </script>
@endsection
