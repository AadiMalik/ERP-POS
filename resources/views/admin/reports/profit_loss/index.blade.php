@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Profit & Loss Statement
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted">
                    Period: {{ localDate($result['start_date']) }} to {{ localDate($result['end_date']) }}
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.profit-loss.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    @endcanAccess
                    @canAccess('reports.profit-loss.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                    @endcanAccess
                    @canAccess('reports.profit-loss.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Excel
                    </a>
                    @endcanAccess
                    @canAccess('reports.profit-loss.export-csv')
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success">
                        <i class="fa fa-file-text"></i> CSV
                    </a>
                    @endcanAccess
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ url('/admin/reports/profit-loss') }}" class="row g-3 border-bottom pb-4 mb-4">
                    @if (RoleNames::SUPERADMIN == getRoleName())
                        <div class="col-md-3">
                            <label class="form-label">Business</label>
                            <select name="business_id" class="form-select">
                                <option value="">--All Businesses--</option>
                                @foreach ($business as $item)
                                    <option value="{{ $item->business_id }}" {{ request('business_id') == $item->business_id ? 'selected' : '' }}>
                                        {{ $item->code ?? '' }} {{ $item->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date', $result['start_date']->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date', $result['end_date']->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <a href="{{ url('/admin/reports/profit-loss') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table">
                        <tbody>
                            <tr class="table-light"><th colspan="2">Revenue / Income</th></tr>
                            @foreach ($result['revenue'] as $row)
                                <tr>
                                    <td class="ps-4">{{ $row->account_code }} {{ $row->account_name }}</td>
                                    <td class="text-end">{{ currency($row->amount) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold"><td>Total Revenue</td><td class="text-end">{{ currency($result['total_revenue']) }}</td></tr>

                            <tr class="table-light"><th colspan="2">Cost of Revenue</th></tr>
                            @foreach ($result['cost_of_revenue'] as $row)
                                <tr>
                                    <td class="ps-4">{{ $row->account_code }} {{ $row->account_name }}</td>
                                    <td class="text-end">{{ currency($row->amount) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold"><td>Total Cost of Revenue</td><td class="text-end">{{ currency($result['total_cost_of_revenue']) }}</td></tr>
                            <tr class="fw-bold table-secondary"><td>Gross Profit</td><td class="text-end">{{ currency($result['gross_profit']) }}</td></tr>

                            <tr class="table-light"><th colspan="2">Direct Expenses</th></tr>
                            @foreach ($result['direct_expense'] as $row)
                                <tr>
                                    <td class="ps-4">{{ $row->account_code }} {{ $row->account_name }}</td>
                                    <td class="text-end">{{ currency($row->amount) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold"><td>Total Direct Expenses</td><td class="text-end">{{ currency($result['total_direct_expense']) }}</td></tr>

                            <tr class="table-light"><th colspan="2">Operating Expenses</th></tr>
                            @foreach ($result['operating_expense'] as $row)
                                <tr>
                                    <td class="ps-4">{{ $row->account_code }} {{ $row->account_name }}</td>
                                    <td class="text-end">{{ currency($row->amount) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold"><td>Total Operating Expenses</td><td class="text-end">{{ currency($result['total_operating_expense']) }}</td></tr>
                            <tr class="fw-bold table-secondary"><td>Operating Profit</td><td class="text-end">{{ currency($result['operating_profit']) }}</td></tr>

                            <tr class="table-light"><th colspan="2">Other Income</th></tr>
                            @foreach ($result['other_income'] as $row)
                                <tr>
                                    <td class="ps-4">{{ $row->account_code }} {{ $row->account_name }}</td>
                                    <td class="text-end">{{ currency($row->amount) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold"><td>Total Other Income</td><td class="text-end">{{ currency($result['total_other_income']) }}</td></tr>

                            <tr class="table-light"><th colspan="2">Other Expenses</th></tr>
                            @foreach ($result['other_expense'] as $row)
                                <tr>
                                    <td class="ps-4">{{ $row->account_code }} {{ $row->account_name }}</td>
                                    <td class="text-end">{{ currency($row->amount) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold"><td>Total Other Expenses</td><td class="text-end">{{ currency($result['total_other_expense']) }}</td></tr>

                            <tr class="fw-bold table-success"><td>Net Profit / (Loss)</td><td class="text-end">{{ currency($result['net_profit']) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function currentReportParams() {
            let params = new URLSearchParams(window.location.search);
            let obj = {};
            params.forEach((value, key) => obj[key] = value);
            return obj;
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + (query ? ('?' + query) : '');
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/profit-loss/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/profit-loss/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/profit-loss/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/profit-loss/export-csv');
        });
    </script>
@endsection
