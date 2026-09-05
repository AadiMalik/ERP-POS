@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.budget_vs_actual') }}</h4>

        <div class="card">
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Budget</label>
                        <select class="form-select" id="budget_id">
                            <option value="">--Select Budget--</option>
                            @foreach ($budgets as $budget)
                                <option value="{{ $budget->budget_id }}">{{ $budget->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-primary" id="loadVarianceBtn">View</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table" id="variance_table">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_account') }}</th>
                                <th>{{ __('common.branch') }}</th>
                                <th>{{ __('reports.col_period') }}</th>
                                <th class="text-end">Budgeted</th>
                                <th class="text-end">Actual</th>
                                <th class="text-end">Variance</th>
                                <th class="text-end">Variance %</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function loadVariance() {
            let budgetId = $("#budget_id").val();
            if (!budgetId) {
                errorMessage("Please select a budget first.");
                return;
            }

            ajaxRequest({
                url: url_local + "/admin/reports/budget-vs-actual/data",
                method: "POST",
                data: { budget_id: budgetId },
            }).then(function(response) {
                let rows = response.Data || [];
                let html = "";
                rows.forEach(function(row) {
                    let varianceClass = row.variance > 0 ? 'text-success' : (row.variance < 0 ? 'text-danger' : '');
                    html += `<tr>
                        <td>${row.account_code ? row.account_code + ' - ' : ''}${row.account_name ?? ''}</td>
                        <td>${row.branch_name ?? 'All'}</td>
                        <td>${row.period_start} to ${row.period_end}</td>
                        <td class="text-end">${parseFloat(row.budgeted).toFixed(2)}</td>
                        <td class="text-end">${parseFloat(row.actual).toFixed(2)}</td>
                        <td class="text-end ${varianceClass}">${parseFloat(row.variance).toFixed(2)}</td>
                        <td class="text-end ${varianceClass}">${row.variance_percent !== null ? row.variance_percent + '%' : 'N/A'}</td>
                    </tr>`;
                });
                $("#variance_table tbody").html(html || '<tr><td colspan="7" class="text-center">No budget lines to compare yet.</td></tr>');
            }).catch(function(err) {
                errorMessage(err.Message || "Could not load report");
            });
        }

        $(document).ready(function() {
            $("#loadVarianceBtn").on("click", loadVariance);
        });
    </script>
@endsection
