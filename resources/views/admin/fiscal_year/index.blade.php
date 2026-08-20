@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Fiscal Years</h4>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>Advanced Accounting Mode</div>
                @canAccess('fiscal-year.create')
                    <button type="button" class="btn btn-primary" id="addFiscalYear">
                        <i class="fa fa-plus"></i> Add Fiscal Year
                    </button>
                @endcanAccess
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="fiscal_year_table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Current</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modelHeading">Add Fiscal Year</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="fiscal_year_form">
                        <div class="modal-body">
                            <input type="hidden" name="fiscal_year_id" id="fiscal_year_id">
                            <div class="mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="start_date" id="start_date" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="end_date" id="end_date" required>
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
    </div>
@endsection
@section('js')
    <script>
        function loadFiscalYears() {
            ajaxRequest({
                url: url_local + "/admin/fiscal-year/data",
                method: "POST",
            }).then(function(response) {
                let rows = response.Data || [];
                let html = "";
                rows.forEach(function(row) {
                    let statusBadge = row.status === 'open'
                        ? '<span class="badge bg-success">Open</span>'
                        : (row.status === 'closed' ? '<span class="badge bg-secondary">Closed</span>' : '<span class="badge bg-warning">Upcoming</span>');
                    html += `<tr>
                        <td>${row.name}</td>
                        <td>${row.start_date}</td>
                        <td>${row.end_date}</td>
                        <td>${statusBadge}</td>
                        <td>${row.is_current ? '<i class="fa fa-check text-success"></i>' : ''}</td>
                        <td>
                            <button class="btn btn-icon btn-outline-primary editFiscalYear" data-id="${row.fiscal_year_id}"><i class="fa fa-pencil"></i></button>
                            <button class="btn btn-icon btn-outline-danger deleteFiscalYear" data-id="${row.fiscal_year_id}"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>`;
                });
                $("#fiscal_year_table tbody").html(html || '<tr><td colspan="6" class="text-center">No fiscal years yet.</td></tr>');
            });
        }

        $(document).ready(function() {
            loadFiscalYears();

            $("#addFiscalYear").on("click", function() {
                $("#fiscal_year_form")[0].reset();
                $("#fiscal_year_id").val("");
                $("#modelHeading").text("Add Fiscal Year");
                $("#ajaxModel").modal("show");
            });

            editRecord({
                buttonClass: ".editFiscalYear",
                url: url_local + "/admin/fiscal-year",
                onSuccess: function(response) {
                    let data = response.Data;
                    $("#fiscal_year_id").val(data.fiscal_year_id);
                    $("#name").val(data.name);
                    $("#start_date").val(data.start_date ? data.start_date.substring(0, 10) : "");
                    $("#end_date").val(data.end_date ? data.end_date.substring(0, 10) : "");
                    $("#modelHeading").text("Edit Fiscal Year");
                    $("#ajaxModel").modal("show");
                }
            });

            saveRecord({
                formId: "#fiscal_year_form",
                url: url_local + "/admin/fiscal-year",
                modalId: "#ajaxModel",
                tableCallback: loadFiscalYears,
            });

            deleteRecord({
                buttonClass: ".deleteFiscalYear",
                url: url_local + "/admin/fiscal-year",
                tableCallback: loadFiscalYears,
            });
        });
    </script>
@endsection
