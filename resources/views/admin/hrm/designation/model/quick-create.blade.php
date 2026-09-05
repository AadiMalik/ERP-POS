{{-- Quick-add Designation modal for use on foreign forms (e.g. Employee
     create). Posts to the existing designation.store route, which now
     returns JSON on AJAX requests while the full
     hrm/designation/create.blade.php page keeps its normal redirect flow.
     Expects a $departments collection in scope (already passed to the host
     page for its own department_id dropdown). The Department field gets its
     own nested quick-add via wireNestedQuickAdd(). --}}
<div class="modal fade" id="quickAddDesignationModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('hrm_designations.add_new_designation') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickAddDesignationForm" name="quickAddDesignationForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="qa_designation_name" name="name" placeholder="{{ __('common.enter_name') }}" required>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <label class="form-label mb-0">{{ __('common.department') }}</label>
                            @include('admin.partials.quick-add-btn', ['permission' => 'department.create', 'modal' => 'quickAddDepartmentModal', 'label' => __('common.department')])
                        </div>
                        <select id="qa_designation_department_id" name="department_id" class="form-select">
                            <option value="">{{ __('hrm_designations.select_department') }}</option>
                            @foreach ($departments ?? [] as $item)
                            <option value="{{ $item->department_id }}">{{ $item->name ?? '' }}</option>
                            @endforeach
                        </select>
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
