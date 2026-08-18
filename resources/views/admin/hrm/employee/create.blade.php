@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ isset($employee) ? 'Update' : 'New' }} Employee</h4>

    <div class="card">
        <form action="{{ url('admin/employee') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="employee_id" value="{{ $employee->employee_id ?? '' }}">

                <h6 class="fw-bold mb-3">Account</h6>
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $employee->user->name ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">Email <span class="text-danger">*</span></label>
                        @if (isset($employee))
                        <input type="email" class="form-control" value="{{ $employee->user->email }}" disabled readonly>
                        <small class="form-text text-muted">Email cannot be changed as it is used for login.</small>
                        @else
                        <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                        <small class="form-text text-muted">A temporary password will be generated automatically.</small>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">Phone</label>
                        <input type="text" class="form-control" name="phone" value="{{ old('phone', $employee->user->phone ?? '') }}">
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Employment</h6>
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <label class="fw-semibold">Employee Code</label>
                        <input type="text" class="form-control" name="employee_code" value="{{ old('employee_code', $employee->employee_code ?? '') }}" placeholder="Auto-generated if left blank">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Department</label>
                        <select name="department_id" class="form-select select2">
                            <option value="">-- Select --</option>
                            @foreach ($departments as $item)
                            <option value="{{ $item->department_id }}" {{ old('department_id', $employee->department_id ?? '') == $item->department_id ? 'selected' : '' }}>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Designation</label>
                        <select name="designation_id" class="form-select select2">
                            <option value="">-- Select --</option>
                            @foreach ($designations as $item)
                            <option value="{{ $item->designation_id }}" {{ old('designation_id', $employee->designation_id ?? '') == $item->designation_id ? 'selected' : '' }}>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Shift</label>
                        <select name="shift_id" class="form-select select2">
                            <option value="">-- Select --</option>
                            @foreach ($shifts as $item)
                            <option value="{{ $item->shift_id }}" {{ old('shift_id', $employee->shift_id ?? '') == $item->shift_id ? 'selected' : '' }}>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Joining Date</label>
                        <input type="date" class="form-control" name="joining_date" value="{{ old('joining_date', $employee->joining_date ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Employment Type</label>
                        <select name="employment_type" class="form-select">
                            @foreach (['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract', 'intern' => 'Intern'] as $key => $label)
                            <option value="{{ $key }}" {{ old('employment_type', $employee->employment_type ?? 'full_time') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if (isset($employee))
                    <div class="col-md-3">
                        <label class="fw-semibold">Status</label>
                        <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $employee->status)) }}" disabled readonly>
                        <small class="form-text text-muted">Change from the Employees list. Resigned/Terminated is set via the Exit workflow.</small>
                    </div>
                    @endif
                </div>

                <h6 class="fw-bold mb-3">Personal</h6>
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <label class="fw-semibold">Date of Birth</label>
                        <input type="date" class="form-control" name="dob" value="{{ old('dob', $employee->dob ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $key => $label)
                            <option value="{{ $key }}" {{ old('gender', $employee->gender ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Marital Status</label>
                        <input type="text" class="form-control" name="marital_status" value="{{ old('marital_status', $employee->marital_status ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">National ID</label>
                        <input type="text" class="form-control" name="national_id" value="{{ old('national_id', $employee->national_id ?? '') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">Address</label>
                        <textarea class="form-control" name="address" rows="2">{{ old('address', $employee->address ?? '') }}</textarea>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Emergency Contact</h6>
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="fw-semibold">Name</label>
                        <input type="text" class="form-control" name="emergency_contact_name" value="{{ old('emergency_contact_name', $employee->emergency_contact_name ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">Phone</label>
                        <input type="text" class="form-control" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">Relation</label>
                        <input type="text" class="form-control" name="emergency_contact_relation" value="{{ old('emergency_contact_relation', $employee->emergency_contact_relation ?? '') }}">
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Bank / Payment Details</h6>
                <div class="row g-4">
                    <div class="col-md-3">
                        <label class="fw-semibold">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="bank" {{ old('payment_method', $employee->payment_method ?? 'bank') == 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="cash" {{ old('payment_method', $employee->payment_method ?? '') == 'cash' ? 'selected' : '' }}>Cash</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Bank Name</label>
                        <input type="text" class="form-control" name="bank_name" value="{{ old('bank_name', $employee->bank_name ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Account Title</label>
                        <input type="text" class="form-control" name="bank_account_title" value="{{ old('bank_account_title', $employee->bank_account_title ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Account Number</label>
                        <input type="text" class="form-control" name="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Branch Code</label>
                        <input type="text" class="form-control" name="bank_branch_code" value="{{ old('bank_branch_code', $employee->bank_branch_code ?? '') }}">
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Save Employee</button>
                </div>
            </div>
        </form>
    </div>

    @if (isset($employee))
    <div class="card mt-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">Documents</h5>
        </div>
        <div class="card-body">
            @can('employee.document')
            <form action="{{ url('admin/employee/' . $employee->employee_id . '/documents') }}" method="POST" enctype="multipart/form-data" class="row g-3 mb-4">
                @csrf
                <div class="col-md-3">
                    <label class="fw-semibold">Document Type <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="document_type" required placeholder="e.g. CNIC, Contract">
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold">File <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" name="file" required>
                </div>
                <div class="col-md-2">
                    <label class="fw-semibold">Expiry Date</label>
                    <input type="date" class="form-control" name="expiry_date">
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold">Notes</label>
                    <input type="text" class="form-control" name="notes">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Upload</button>
                </div>
            </form>
            @endcan

            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>File</th>
                        <th>Expiry</th>
                        <th>Notes</th>
                        <th>Action</th>
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
                    <tr><td colspan="5" class="text-center text-muted">No documents uploaded yet.</td></tr>
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
    deleteRecord({
        buttonClass: "#deleteEmployeeDocument",
        url: url_local + "/admin/employee-document",
        tableCallback: function() {
            window.location.reload();
        }
    });
</script>
@endsection
