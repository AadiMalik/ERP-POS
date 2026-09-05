@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.monthly_attendance_report') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.monthly-attendance-report.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.monthly-attendance-report.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.monthly-attendance-report.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.monthly-attendance-report.export-csv')
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success">
                        <i class="fa fa-file-text"></i> {{ __('common.csv') }}
                    </a>
                    @endcanAccess
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">{{ __('common.business') }}</label>
                                <select id="business_id" class="form-select">
                                    <option value="">{{ __('common.all_businesses') }}</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ $item->code ?? '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-2">
                            <label class="form-label">{{ __('common.month') }}</label>
                            <select id="month" class="form-select">
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('common.year') }}</label>
                            <select id="year" class="form-select">
                                @foreach (range(now()->year, now()->year - 5) as $y)
                                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.department') }}</label>
                            <select id="department_id" class="form-select">
                                <option value="">{{ __('common.all_departments') }}</option>
                                @foreach ($departments as $item)
                                    <option value="{{ $item->department_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_code') }}</th>
                                <th>{{ __('common.name') }}</th>
                                <th>{{ __('reports.col_department') }}</th>
                                @for ($d = 1; $d <= $daysInMonth; $d++)
                                    <th class="text-center">{{ $d }}</th>
                                @endfor
                                <th class="text-end">{{ __('reports.col_present') }}</th>
                                <th class="text-end">{{ __('reports.col_absent') }}</th>
                                <th class="text-end">Leave</th>
                                <th class="text-end">{{ __('reports.col_hours') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>{{ $row->employee_code }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ $row->department }}</td>
                                    @for ($d = 1; $d <= $daysInMonth; $d++)
                                        <td class="text-center">{{ $row->days[$d] ?? '' }}</td>
                                    @endfor
                                    <td class="text-end">{{ $row->present_count }}</td>
                                    <td class="text-end">{{ $row->absent_count }}</td>
                                    <td class="text-end">{{ $row->leave_count }}</td>
                                    <td class="text-end">{{ $row->total_working_hours }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 7 + $daysInMonth }}" class="text-center">No records found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <small class="text-muted">P = Present, A = Absent, L = Late, HD = Half Day, LV = Leave, H = Holiday</small>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                month: $('#month').val() || '',
                year: $('#year').val() || '',
                department_id: $('#department_id').val() || '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id, #month, #year, #department_id').select2();
        });

        $('#search_btn').click(function() {
            window.location.href = buildReportUrl('/admin/reports/monthly-attendance-report');
        });

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/monthly-attendance-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/monthly-attendance-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/monthly-attendance-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/monthly-attendance-report/export-csv');
        });
    </script>
@endsection
