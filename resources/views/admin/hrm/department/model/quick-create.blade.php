{{-- Quick-add Department modal for use on foreign forms (e.g. Employee,
     Designation create). Posts to the existing department.store route,
     which now returns JSON on AJAX requests while the full
     hrm/department/create.blade.php page keeps its normal redirect flow.
     business_id/branch_id are always taken from Auth::user() server-side. --}}
<div class="modal fade" id="quickAddDepartmentModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickAddDepartmentForm" name="quickAddDepartmentForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="qa_department_name" name="name" placeholder="Enter Name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
