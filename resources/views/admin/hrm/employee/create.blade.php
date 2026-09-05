@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ isset($employee) ? __('hrm_employees.update_heading') : __('hrm_employees.new_heading') }}</h4>

    <div class="card">
        <form action="{{ url('admin/employee') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="employee_id" value="{{ $employee->employee_id ?? '' }}">

                <h6 class="fw-bold mb-3">{{ __('hrm_employees.account_section') }}</h6>
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_employees.full_name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $employee->user->name ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('common.email') }} <span class="text-danger">*</span></label>
                        @if (isset($employee))
                        <input type="email" class="form-control" value="{{ $employee->user->email }}" disabled readonly>
                        <small class="form-text text-muted">{{ __('hrm_employees.email_locked_hint') }}</small>
                        @else
                        <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                        <small class="form-text text-muted">{{ __('hrm_employees.temp_password_hint') }}</small>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('common.phone') }}</label>
                        <input type="text" class="form-control" name="phone" value="{{ old('phone', $employee->user->phone ?? '') }}">
                    </div>
                </div>

                <h6 class="fw-bold mb-3">{{ __('hrm_employees.employment_section') }}</h6>
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <label class="fw-semibold">{{ __('hrm_employees.employee_code') }}</label>
                        <input type="text" class="form-control" name="employee_code" value="{{ old('employee_code', $employee->employee_code ?? '') }}" placeholder="{{ __('hrm_employees.code_placeholder') }}">
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <label class="fw-semibold mb-0">{{ __('hrm_employees.department') }}</label>
                            @include('admin.partials.quick-add-btn', ['permission' => 'department.create', 'modal' => 'quickAddDepartmentModal', 'label' => 'Department'])
                        </div>
                        <select name="department_id" id="department_id" class="form-select select2">
                            <option value="">{{ __('common.select_option') }}</option>
                            @foreach ($departments as $item)
                            <option value="{{ $item->department_id }}" {{ old('department_id', $employee->department_id ?? '') == $item->department_id ? 'selected' : '' }}>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <label class="fw-semibold mb-0">{{ __('hrm_employees.designation') }}</label>
                            @include('admin.partials.quick-add-btn', ['permission' => 'designation.create', 'modal' => 'quickAddDesignationModal', 'label' => 'Designation'])
                        </div>
                        <select name="designation_id" id="designation_id" class="form-select select2">
                            <option value="">{{ __('common.select_option') }}</option>
                            @foreach ($designations as $item)
                            <option value="{{ $item->designation_id }}" {{ old('designation_id', $employee->designation_id ?? '') == $item->designation_id ? 'selected' : '' }}>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <label class="fw-semibold mb-0">{{ __('hrm_employees.shift') }}</label>
                            @include('admin.partials.quick-add-btn', ['permission' => 'shift.create', 'modal' => 'quickAddShiftModal', 'label' => 'Shift'])
                        </div>
                        <select name="shift_id" id="shift_id" class="form-select select2">
                            <option value="">{{ __('common.select_option') }}</option>
                            @foreach ($shifts as $item)
                            <option value="{{ $item->shift_id }}" {{ old('shift_id', $employee->shift_id ?? '') == $item->shift_id ? 'selected' : '' }}>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">{{ __('hrm_employees.joining_date') }}</label>
                        <input type="date" class="form-control" name="joining_date" value="{{ old('joining_date', $employee->joining_date ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">{{ __('hrm_employees.employment_type') }}</label>
                        <select name="employment_type" class="form-select">
                            @foreach (['full_time' => __('hrm_employees.full_time'), 'part_time' => __('hrm_employees.part_time'), 'contract' => __('hrm_employees.contract'), 'intern' => __('hrm_employees.intern')] as $key => $label)
                            <option value="{{ $key }}" {{ old('employment_type', $employee->employment_type ?? 'full_time') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if (isset($employee))
                    <div class="col-md-3">
                        <label class="fw-semibold">{{ __('common.status') }}</label>
                        <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $employee->status)) }}" disabled readonly>
                        <small class="form-text text-muted">{{ __('hrm_employees.status_change_hint') }}</small>
                    </div>
                    @endif
                </div>

                <h6 class="fw-bold mb-3">{{ __('hrm_employees.personal_section') }}</h6>
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <label class="fw-semibold">{{ __('hrm_employees.date_of_birth') }}</label>
                        <input type="date" class="form-control" name="dob" value="{{ old('dob', $employee->dob ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">{{ __('hrm_employees.gender') }}</label>
                        <select name="gender" class="form-select">
                            <option value="">{{ __('common.select_option') }}</option>
                            @foreach (['male' => __('common.male'), 'female' => __('common.female'), 'other' => __('common.other')] as $key => $label)
                            <option value="{{ $key }}" {{ old('gender', $employee->gender ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">{{ __('hrm_employees.marital_status') }}</label>
                        <input type="text" class="form-control" name="marital_status" value="{{ old('marital_status', $employee->marital_status ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">{{ __('hrm_employees.national_id') }}</label>
                        <input type="text" class="form-control" name="national_id" value="{{ old('national_id', $employee->national_id ?? '') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">{{ __('common.address') }}</label>
                        <textarea class="form-control" name="address" rows="2">{{ old('address', $employee->address ?? '') }}</textarea>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">{{ __('hrm_employees.emergency_contact') }}</h6>
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('common.name') }}</label>
                        <input type="text" class="form-control" name="emergency_contact_name" value="{{ old('emergency_contact_name', $employee->emergency_contact_name ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('common.phone') }}</label>
                        <input type="text" class="form-control" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_employees.relation') }}</label>
                        <input type="text" class="form-control" name="emergency_contact_relation" value="{{ old('emergency_contact_relation', $employee->emergency_contact_relation ?? '') }}">
                    </div>
                </div>

                <h6 class="fw-bold mb-3">{{ __('hrm_employees.bank_payment_details') }}</h6>
                <div class="row g-4">
                    <div class="col-md-3">
                        <label class="fw-semibold">{{ __('common.payment_method') }}</label>
                        <select name="payment_method" class="form-select">
                            <option value="bank" {{ old('payment_method', $employee->payment_method ?? 'bank') == 'bank' ? 'selected' : '' }}>{{ __('common.bank_transfer') }}</option>
                            <option value="cash" {{ old('payment_method', $employee->payment_method ?? '') == 'cash' ? 'selected' : '' }}>{{ __('common.cash') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">{{ __('hrm_employees.bank_name') }}</label>
                        <input type="text" class="form-control" name="bank_name" value="{{ old('bank_name', $employee->bank_name ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">{{ __('hrm_employees.account_title') }}</label>
                        <input type="text" class="form-control" name="bank_account_title" value="{{ old('bank_account_title', $employee->bank_account_title ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">{{ __('hrm_employees.account_number') }}</label>
                        <input type="text" class="form-control" name="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">{{ __('hrm_employees.branch_code') }}</label>
                        <input type="text" class="form-control" name="bank_branch_code" value="{{ old('bank_branch_code', $employee->bank_branch_code ?? '') }}">
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('hrm_employees.save_employee') }}</button>
                </div>
            </div>
        </form>
    </div>

    @include('admin.hrm.department.model.quick-create')
    @include('admin.hrm.designation.model.quick-create', ['departments' => $departments])
    @include('admin.hrm.shift.model.quick-create')

    @if (isset($employee))
    <div class="card mt-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ __('hrm_employees.documents') }}</h5>
        </div>
        <div class="card-body">
            @can('employee.document')
            <form action="{{ url('admin/employee/' . $employee->employee_id . '/documents') }}" method="POST" enctype="multipart/form-data" class="row g-3 mb-4">
                @csrf
                <div class="col-md-3">
                    <label class="fw-semibold">{{ __('hrm_employees.document_type') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="document_type" required placeholder="{{ __('hrm_employees.document_type_placeholder') }}">
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold">{{ __('common.file') }} <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" name="file" required>
                </div>
                <div class="col-md-2">
                    <label class="fw-semibold">{{ __('common.expiry_date') }}</label>
                    <input type="date" class="form-control" name="expiry_date">
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold">{{ __('common.notes') }}</label>
                    <input type="text" class="form-control" name="notes">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-primary w-100">{{ __('hrm_employees.upload_document') }}</button>
                </div>
            </form>
            @endcan

            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('common.type') }}</th>
                        <th>{{ __('common.file') }}</th>
                        <th>{{ __('common.expiry') }}</th>
                        <th>{{ __('common.notes') }}</th>
                        <th>{{ __('common.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employee->documents as $doc)
                    <tr>
                        <td>{{ $doc->document_type }}</td>
                        <td><a href="{{ asset($doc->file_path) }}" target="_blank">{{ $doc->file_name }}</a></td>
                        <td>{{ $doc->expiry_date ?? '-' }}</td>
                        <td>{{ $doc->notes ?? '-' }}</td>
                        <td>
                            @can('employee.document')
                            <a class="btn btn-icon btn-outline-danger" id="deleteEmployeeDocument" data-id="{{ $doc->employee_document_id }}">
                                <i class="fa fa-trash"></i>
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted">{{ __('hrm_employees.no_documents') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection

@section('js')
@if ($errors->any())
<script>
    errorMessage("{{ $errors->first() }}");
</script>
@endif
@if(session('error'))
<script>
    errorMessage("{{ session('error') }}");
</script>
@endif
<script>
    $(document).ready(function() {
        $('.select2').select2();
    });

    initQuickAdd({
        modalId: '#quickAddDepartmentModal',
        formId: '#quickAddDepartmentForm',
        url: url_local + '/admin/department',
        valueField: 'department_id',
        labelField: 'name',
        targetSelectIds: ['department_id', 'qa_designation_department_id'],
    });

    wireNestedQuickAdd('#quickAddDesignationModal', '#quickAddDepartmentModal');

    initQuickAdd({
        modalId: '#quickAddDesignationModal',
        formId: '#quickAddDesignationForm',
        url: url_local + '/admin/designation',
        valueField: 'designation_id',
        labelField: 'name',
        targetSelectIds: ['designation_id'],
    });

    initQuickAdd({
        modalId: '#quickAddShiftModal',
        formId: '#quickAddShiftForm',
        url: url_local + '/admin/shift',
        valueField: 'shift_id',
        labelField: 'name',
        targetSelectIds: ['shift_id'],
        beforeSubmit: function() {
            if ($('#quickAddShiftForm input[name="working_days[]"]:checked').length === 0) {
                errorMessage('Please select at least one working day.');
                return false;
            }
            return true;
        },
    });

    deleteRecord({
        buttonClass: "#deleteEmployeeDocument",
        url: url_local + "/admin/employee-document",
        tableCallback: function() {
            window.location.reload();
        }
    });
</script>
@endsection
