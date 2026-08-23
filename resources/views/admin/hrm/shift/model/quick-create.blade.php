@php
$qa_shift_days = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];
@endphp
{{-- Quick-add Shift modal for use on foreign forms (e.g. Employee create).
     Posts to the existing shift.store route, which now returns JSON on AJAX
     requests while the full hrm/shift/create.blade.php page keeps its normal
     redirect flow. --}}
<div class="modal fade" id="quickAddShiftModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickAddShiftForm" name="quickAddShiftForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="qa_shift_name" name="name" placeholder="Enter Name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="qa_shift_start_time" name="start_time" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="qa_shift_end_time" name="end_time" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label d-block">Working Days <span class="text-danger">*</span></label>
                            @foreach ($qa_shift_days as $key => $label)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="working_days[]" value="{{ $key }}" id="qa_shift_day_{{ $key }}">
                                <label class="form-check-label" for="qa_shift_day_{{ $key }}">{{ $label }}</label>
                            </div>
                            @endforeach
                        </div>
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
