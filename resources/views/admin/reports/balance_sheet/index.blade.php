@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Balance Sheet
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted">
                    As of: {{ localDate($result['as_of_date']) }}
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
                <form method="GET" action="{{ url('/admin/reports/balance-sheet') }}" class="row g-3 border-bottom pb-4 mb-4">
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
                        <label class="form-label">As Of Date</label>
                        <input type="date" name="as_of_date" class="form-control" value="{{ request('as_of_date', $result['as_of_date']->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <a href="{{ url('/admin/reports/balance-sheet') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>

                <div class="row">
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table">
                                <tbody>
                                    <tr class="table-primary"><th colspan="2">Assets</th></tr>
                                    <tr class="table-light"><th colspan="2">Current Assets</th></tr>
                                    @foreach ($result['current_assets'] as $row)
                                        <tr><td class="ps-4">{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-end">{{ currency($row->amount) }}</td></tr>
                                    @endforeach
                                    <tr class="fw-bold"><td>Total Current Assets</td><td class="text-end">{{ currency($result['total_current_assets']) }}</td></tr>

                                    <tr class="table-light"><th colspan="2">Fixed Assets</th></tr>
                                    @foreach ($result['fixed_assets'] as $row)
                                        <tr><td class="ps-4">{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-end">{{ currency($row->amount) }}</td></tr>
                                    @endforeach
                                    <tr class="fw-bold"><td>Total Fixed Assets</td><td class="text-end">{{ currency($result['total_fixed_assets']) }}</td></tr>

                                    @if ($result['other_assets']->isNotEmpty())
                                        <tr class="table-light"><th colspan="2">Other Assets</th></tr>
                                        @foreach ($result['other_assets'] as $row)
                                            <tr><td class="ps-4">{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-end">{{ currency($row->amount) }}</td></tr>
                                        @endforeach
                                        <tr class="fw-bold"><td>Total Other Assets</td><td class="text-end">{{ currency($result['total_other_assets']) }}</td></tr>
                                    @endif

                                    <tr class="fw-bold table-secondary"><td>Total Assets</td><td class="text-end">{{ currency($result['total_assets']) }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table">
                                <tbody>
                                    <tr class="table-primary"><th colspan="2">Liabilities</th></tr>
                                    <tr class="table-light"><th colspan="2">Current Liabilities</th></tr>
                                    @foreach ($result['current_liabilities'] as $row)
                                        <tr><td class="ps-4">{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-end">{{ currency($row->amount) }}</td></tr>
                                    @endforeach
                                    <tr class="fw-bold"><td>Total Current Liabilities</td><td class="text-end">{{ currency($result['total_current_liabilities']) }}</td></tr>

                                    @if ($result['long_term_liabilities']->isNotEmpty())
                                        <tr class="table-light"><th colspan="2">Long-term Liabilities</th></tr>
                                        @foreach ($result['long_term_liabilities'] as $row)
                                            <tr><td class="ps-4">{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-end">{{ currency($row->amount) }}</td></tr>
                                        @endforeach
                                        <tr class="fw-bold"><td>Total Long-term Liabilities</td><td class="text-end">{{ currency($result['total_long_term_liabilities']) }}</td></tr>
                                    @endif

                                    @if ($result['other_liabilities']->isNotEmpty())
                                        <tr class="table-light"><th colspan="2">Other Liabilities</th></tr>
                                        @foreach ($result['other_liabilities'] as $row)
                                            <tr><td class="ps-4">{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-end">{{ currency($row->amount) }}</td></tr>
                                        @endforeach
                                        <tr class="fw-bold"><td>Total Other Liabilities</td><td class="text-end">{{ currency($result['total_other_liabilities']) }}</td></tr>
                                    @endif

                                    <tr class="fw-bold table-secondary"><td>Total Liabilities</td><td class="text-end">{{ currency($result['total_liabilities']) }}</td></tr>

                                    <tr class="table-primary"><th colspan="2">Equity</th></tr>
                                    @foreach ($result['equity'] as $row)
                                        <tr><td class="ps-4">{{ trim($row->account_code . ' ' . $row->account_name) }}</td><td class="text-end">{{ currency($row->amount) }}</td></tr>
                                    @endforeach
                                    <tr class="fw-bold"><td>Total Equity</td><td class="text-end">{{ currency($result['total_equity']) }}</td></tr>

                                    <tr class="fw-bold table-success"><td>Total Liabilities & Equity</td><td class="text-end">{{ currency($result['total_liabilities_and_equity']) }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
            window.open(buildReportUrl('/admin/reports/balance-sheet/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/balance-sheet/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/balance-sheet/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/balance-sheet/export-csv');
        });
    </script>
@endsection
