@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Attendance Register
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>
                </div>
                <div class="d-flex gap-2">
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Excel
                    </a>
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success">
                        <i class="fa fa-file-text"></i> CSV
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">Business</label>
                                <select id="business_id" class="form-select">
                                    <option value="">--All Businesses--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ $item->code ?? '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Department</label>
                            <select id="department_id" class="form-select">
                                <option value="">--All Departments--</option>
                                @foreach ($departments as $item)
                                    <option value="{{ $item->department_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <select id="branch_id" class="form-select">
                                <option value="">--All Branches--</option>
                                @foreach ($branches as $item)
                                    <option value="{{ $item->branch_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date Range</label>
                            <input type="date" id="start_date" class="form-control mb-1" value="{{ now()->startOfMonth()->toDateString() }}">
                            <input type="date" id="end_date" class="form-control" value="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">
                                Search
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Department</th>
                                @foreach ($dates as $date)
                                    <th class="text-center">{{ $date->format('d M') }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>{{ $row->employee_code }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ $row->department }}</td>
                                    @foreach ($row->days as $value)
                                        <td class="text-center">{{ $value }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 3 + count($dates) }}" class="text-center">No records found</td>
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
                department_id: $('#department_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                start_date: $('#start_date').val() || '',
                end_date: $('#end_date').val() || '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id, #department_id, #branch_id').select2();
        });

        $('#search_btn').click(function() {
            window.location.href = buildReportUrl('/admin/reports/attendance-register');
        });

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/attendance-register/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/attendance-register/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/attendance-register/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/attendance-register/export-csv');
        });
    </script>
@endsection
