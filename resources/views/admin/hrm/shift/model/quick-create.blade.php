@php
$qa_shift_days = [
    'mon' => __('hrm_shifts.day_mon'),
    'tue' => __('hrm_shifts.day_tue'),
    'wed' => __('hrm_shifts.day_wed'),
    'thu' => __('hrm_shifts.day_thu'),
    'fri' => __('hrm_shifts.day_fri'),
    'sat' => __('hrm_shifts.day_sat'),
    'sun' => __('hrm_shifts.day_sun'),
];
@endphp
{{-- Quick-add Shift modal for use on foreign forms (e. g. Employee create).
     Posts to the existing shift.store route, which now returns JSON on AJAX
     requests while the full hrm/shift/create.blade.php page keeps its normal
     redirect flow. --}}
<div class="modal fade" id="quickAddShiftModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('hrm_shifts.add_new_shift') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickAddShiftForm" name="quickAddShiftForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">{{ __('common.name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="qa_shift_name" name="name" placeholder="{{ __('common.enter_name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('hrm_shifts.start_time') }} <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="qa_shift_start_time" name="start_time" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('hrm_shifts.end_time') }} <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="qa_shift_end_time" name="end_time" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label d-block">{{ __('hrm_shifts.working_days') }} <span class="text-danger">*</span></label>
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
