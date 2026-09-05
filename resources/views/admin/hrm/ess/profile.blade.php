@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_ess.my_profile') }}</h4>

    <div class="alert alert-info">
        {!! __('hrm_ess.profile_update_hint', ['link' => '<a href="' . url('admin/profile') . '">' . e(__('hrm_ess.my_account')) . '</a>']) !!}
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white border-bottom"><h5 class="mb-0">{{ __('hrm_ess.employment_details') }}</h5></div>
        <div class="card-body">
            <div class="row g-3">
                @php
                    $empType = strtolower(str_replace(' ', '_', (string) $employee->employment_type));
                    $empTypeLabel = match ($empType) {
                        'full_time' => __('hrm_ess.full_time'),
                        'part_time' => __('hrm_ess.part_time'),
                        'contract' => __('hrm_ess.contract'),
                        'intern' => __('hrm_ess.intern'),
                        default => ucfirst(str_replace('_', ' ', (string) $employee->employment_type)),
                    };
                    $empStatus = strtolower(str_replace(' ', '_', (string) $employee->status));
                    $empStatusLabel = match ($empStatus) {
                        'active' => __('hrm_ess.active'),
                        'inactive' => __('hrm_ess.inactive'),
                        'on_leave' => __('hrm_ess.on_leave'),
                        'resigned' => __('hrm_ess.resigned'),
                        'terminated' => __('hrm_ess.terminated'),
                        default => ucfirst(str_replace('_', ' ', (string) $employee->status)),
                    };
                @endphp
                <div class="col-md-4"><strong>{{ __('hrm_ess.employee_code') }}:</strong> {{ $employee->employee_code }}</div>
                <div class="col-md-4"><strong>{{ __('common.department') }}:</strong> {{ $employee->department->name ?? '-' }}</div>
                <div class="col-md-4"><strong>{{ __('common.designation') }}:</strong> {{ $employee->designation->name ?? '-' }}</div>
                <div class="col-md-4"><strong>{{ __('hrm_ess.shift') }}:</strong> {{ $employee->shift->name ?? '-' }}</div>
                <div class="col-md-4"><strong>{{ __('hrm_ess.joining_date') }}:</strong> {{ $employee->joining_date ?? '-' }}</div>
                <div class="col-md-4"><strong>{{ __('hrm_ess.employment_type') }}:</strong> {{ $empTypeLabel }}</div>
                <div class="col-md-4"><strong>{{ __('common.status') }}:</strong> {{ $empStatusLabel }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white border-bottom"><h5 class="mb-0">{{ __('hrm_ess.personal_details') }}</h5></div>
        <div class="card-body">
            <div class="row g-3">
                @php
                    $genderLabel = match (strtolower((string) $employee->gender)) {
                        'male' => __('hrm_ess.male'),
                        'female' => __('hrm_ess.female'),
                        'other' => __('hrm_ess.other'),
                        default => $employee->gender ? ucfirst($employee->gender) : '-',
                    };
                @endphp
                <div class="col-md-4"><strong>{{ __('hrm_ess.date_of_birth') }}:</strong> {{ $employee->dob ?? '-' }}</div>
                <div class="col-md-4"><strong>{{ __('hrm_ess.gender') }}:</strong> {{ $genderLabel }}</div>
                <div class="col-md-4"><strong>{{ __('hrm_ess.national_id') }}:</strong> {{ $employee->national_id ?? '-' }}</div>
                <div class="col-md-12"><strong>{{ __('common.address') }}:</strong> {{ $employee->address ?? '-' }}</div>
                <div class="col-md-4"><strong>{{ __('hrm_ess.emergency_contact') }}:</strong> {{ $employee->emergency_contact_name ?? '-' }}</div>
                <div class="col-md-4"><strong>{{ __('common.phone') }}:</strong> {{ $employee->emergency_contact_phone ?? '-' }}</div>
                <div class="col-md-4"><strong>{{ __('hrm_ess.relation') }}:</strong> {{ $employee->emergency_contact_relation ?? '-' }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white border-bottom"><h5 class="mb-0">{{ __('hrm_ess.my_allocated_assets') }}</h5></div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>{{ __('hrm_ess.asset') }}</th><th>{{ __('hrm_ess.issue_date') }}</th><th>{{ __('hrm_ess.return_date') }}</th><th>{{ __('common.status') }}</th></tr></thead>
                <tbody>
                    @forelse ($allocated_assets as $allocation)
                    <tr>
                        <td>{{ $allocation->asset->name ?? '-' }}</td>
                        <td>{{ $allocation->issue_date }}</td>
                        <td>{{ $allocation->return_date ?? '-' }}</td>
                        <td><span class="badge bg-label-secondary">{{ ucfirst($allocation->status) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted">{{ __('hrm_ess.no_assets_allocated') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
