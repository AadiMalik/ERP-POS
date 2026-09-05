@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_salary_structures.title') }}</h4>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('common.employee') }}</th>
                            <th>{{ __('common.code') }}</th>
                            <th>{{ __('hrm_salary_structures.current_basic_salary') }}</th>
                            <th>{{ __('hrm_salary_structures.effective_from') }}</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                        <tr>
                            <td>{{ $employee->user->name ?? '-' }}</td>
                            <td>{{ $employee->employee_code }}</td>
                            <td>{{ $employee->activeSalaryStructure ? number_format($employee->activeSalaryStructure->basic_salary, 2) : '-' }}</td>
                            <td>{{ $employee->activeSalaryStructure->effective_from ?? '-' }}</td>
                            <td>
                                <a href="{{ url('admin/salary-structure/' . $employee->employee_id) }}" class="btn btn-sm btn-outline-primary">
                                    {{ $employee->activeSalaryStructure ? __('hrm_salary_structures.manage') : __('hrm_salary_structures.assign_structure') }}
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">{{ __('hrm_salary_structures.no_employees') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
