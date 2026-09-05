@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.employee_asset_return_report') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.employee-asset-return-report.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.employee-asset-return-report.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.employee-asset-return-report.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.employee-asset-return-report.export-csv')
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
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.employee') }}</label>
                            <select id="employee_id" class="form-select">
                                <option value="">{{ __('common.all_employees') }}</option>
                                @foreach ($employees as $item)
                                    <option value="{{ $item->employee_id }}">{{ $item->user?->name }} ({{ $item->employee_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Return Status</label>
                            <select id="return_status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="overdue">Overdue</option>
                                <option value="returned">Returned</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="employee_asset_return_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_asset_tag') }}</th>
                                <th>{{ __('reports.col_asset_name') }}</th>
                                <th>{{ __('reports.col_employee') }}</th>
                                <th>{{ __('reports.col_department') }}</th>
                                <th>{{ __('reports.col_issue_date') }}</th>
                                <th>{{ __('reports.col_expected_return') }}</th>
                                <th>{{ __('reports.col_return_date') }}</th>
                                <th>{{ __('common.status') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'asset_tag',name:'asset_tag',sortable:false},
                        {data:'asset_name',name:'asset_name',sortable:false},
                        {data:'name',name:'name',sortable:false},
                        {data:'department',name:'department',sortable:false},
                        {data:'issue_date',name:'issue_date',sortable:false},
                        {data:'expected_return_date',name:'expected_return_date',sortable:false},
                        {data:'return_date',name:'return_date',sortable:false},
                        {data:'status',name:'status',sortable:false}",
        'route' => 'employee-asset-return-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'employee_asset_return_report_table',
        'variable' => 'employee_asset_return_report_table',
        'params' => "business_id:$('#business_id').val(),employee_id:$('#employee_id').val(),return_status:$('#return_status').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                employee_id: $('#employee_id').val() || '',
                return_status: $('#return_status').val() || '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id, #employee_id, #return_status').select2();
        });

        $('#search_btn').click(function() {
            initDataTableemployee_asset_return_report_table();
        });

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/employee-asset-return-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/employee-asset-return-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/employee-asset-return-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/employee-asset-return-report/export-csv');
        });
    </script>
@endsection
