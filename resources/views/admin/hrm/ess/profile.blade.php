@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">My Profile</h4>

    <div class="alert alert-info">
        To update your name, phone, or password, visit <a href="{{ url('admin/profile') }}">My Account</a>.
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white border-bottom"><h5 class="mb-0">Employment Details</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Employee Code:</strong> {{ $employee->employee_code }}</div>
                <div class="col-md-4"><strong>Department:</strong> {{ $employee->department->name ?? '-' }}</div>
                <div class="col-md-4"><strong>Designation:</strong> {{ $employee->designation->name ?? '-' }}</div>
                <div class="col-md-4"><strong>Shift:</strong> {{ $employee->shift->name ?? '-' }}</div>
                <div class="col-md-4"><strong>Joining Date:</strong> {{ $employee->joining_date ?? '-' }}</div>
                <div class="col-md-4"><strong>Employment Type:</strong> {{ ucfirst(str_replace('_', ' ', $employee->employment_type)) }}</div>
                <div class="col-md-4"><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $employee->status)) }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white border-bottom"><h5 class="mb-0">Personal Details</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Date of Birth:</strong> {{ $employee->dob ?? '-' }}</div>
                <div class="col-md-4"><strong>Gender:</strong> {{ $employee->gender ? ucfirst($employee->gender) : '-' }}</div>
                <div class="col-md-4"><strong>National ID:</strong> {{ $employee->national_id ?? '-' }}</div>
                <div class="col-md-12"><strong>Address:</strong> {{ $employee->address ?? '-' }}</div>
                <div class="col-md-4"><strong>Emergency Contact:</strong> {{ $employee->emergency_contact_name ?? '-' }}</div>
                <div class="col-md-4"><strong>Phone:</strong> {{ $employee->emergency_contact_phone ?? '-' }}</div>
                <div class="col-md-4"><strong>Relation:</strong> {{ $employee->emergency_contact_relation ?? '-' }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white border-bottom"><h5 class="mb-0">My Allocated Assets</h5></div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Asset</th><th>Issue Date</th><th>Return Date</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($allocated_assets as $allocation)
                    <tr>
                        <td>{{ $allocation->asset->name ?? '-' }}</td>
                        <td>{{ $allocation->issue_date }}</td>
                        <td>{{ $allocation->return_date ?? '-' }}</td>
                        <td><span class="badge bg-label-secondary">{{ ucfirst($allocation->status) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted">No assets allocated.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
