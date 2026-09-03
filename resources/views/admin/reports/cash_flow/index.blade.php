@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Cash Flow Statement
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted">
                    Period: {{ localDate($result['start_date']) }} to {{ localDate($result['end_date']) }}
                    @if ($result['cash_accounts_count'] > 0)
                        · {{ $result['cash_accounts_count'] }} cash/bank account(s)
                    @endif
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.cash-flow.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    @endcanAccess
                    @canAccess('reports.cash-flow.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                    @endcanAccess
                    @canAccess('reports.cash-flow.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Excel
                    </a>
                    @endcanAccess
                    @canAccess('reports.cash-flow.export-csv')
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success">
                        <i class="fa fa-file-text"></i> CSV
                    </a>
                    @endcanAccess
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ url('/admin/reports/cash-flow') }}" class="row g-3 border-bottom pb-4 mb-4">
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
                        <a href="{{ url('/admin/reports/cash-flow') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>

                @if ($result['cash_accounts_count'] === 0)
                    <div class="alert alert-warning mb-0">
                        No cash or bank accounts were found for this business. Ensure Chart of Accounts includes
                        accounts under the Cash &amp; Cash Equivalents sub-type, or set default cash/bank accounts
                        in Accounting Settings.
                    </div>
                @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Particulars</th>
                                <th class="text-end">Inflow</th>
                                <th class="text-end">Outflow</th>
                                <th class="text-end">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-light"><th colspan="4">Cash flows from operating activities</th></tr>
                            @forelse ($result['operating'] as $row)
                                <tr>
                                    <td class="ps-4">{{ $row->label }}</td>
                                    <td class="text-end">{{ $row->inflow > 0 ? currency($row->inflow) : '' }}</td>
                                    <td class="text-end">{{ $row->outflow > 0 ? currency($row->outflow) : '' }}</td>
                                    <td class="text-end">{{ currency($row->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td class="ps-4 text-muted" colspan="4">No operating cash movements in this period</td></tr>
                            @endforelse
                            <tr class="fw-bold table-secondary">
                                <td>Net cash from operating activities</td>
                                <td></td><td></td>
                                <td class="text-end">{{ currency($result['net_operating']) }}</td>
                            </tr>

                            <tr class="table-light"><th colspan="4">Cash flows from investing activities</th></tr>
                            @forelse ($result['investing'] as $row)
                                <tr>
                                    <td class="ps-4">{{ $row->label }}</td>
                                    <td class="text-end">{{ $row->inflow > 0 ? currency($row->inflow) : '' }}</td>
                                    <td class="text-end">{{ $row->outflow > 0 ? currency($row->outflow) : '' }}</td>
                                    <td class="text-end">{{ currency($row->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td class="ps-4 text-muted" colspan="4">No investing cash movements in this period</td></tr>
                            @endforelse
                            <tr class="fw-bold table-secondary">
                                <td>Net cash from investing activities</td>
                                <td></td><td></td>
                                <td class="text-end">{{ currency($result['net_investing']) }}</td>
                            </tr>

                            <tr class="table-light"><th colspan="4">Cash flows from financing activities</th></tr>
                            @forelse ($result['financing'] as $row)
                                <tr>
                                    <td class="ps-4">{{ $row->label }}</td>
                                    <td class="text-end">{{ $row->inflow > 0 ? currency($row->inflow) : '' }}</td>
                                    <td class="text-end">{{ $row->outflow > 0 ? currency($row->outflow) : '' }}</td>
                                    <td class="text-end">{{ currency($row->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td class="ps-4 text-muted" colspan="4">No financing cash movements in this period</td></tr>
                            @endforelse
                            <tr class="fw-bold table-secondary">
                                <td>Net cash from financing activities</td>
                                <td></td><td></td>
                                <td class="text-end">{{ currency($result['net_financing']) }}</td>
                            </tr>

                            <tr class="fw-bold table-success">
                                <td>Net increase / (decrease) in cash &amp; bank</td>
                                <td class="text-end">{{ currency($result['total_inflows']) }}</td>
                                <td class="text-end">{{ currency($result['total_outflows']) }}</td>
                                <td class="text-end">{{ currency($result['net_increase']) }}</td>
                            </tr>
                            <tr class="fw-bold">
                                <td>Opening cash &amp; bank balance</td>
                                <td></td><td></td>
                                <td class="text-end">{{ currency($result['opening_cash']) }}</td>
                            </tr>
                            <tr class="fw-bold">
                                <td>Closing cash &amp; bank balance</td>
                                <td></td><td></td>
                                <td class="text-end">{{ currency($result['closing_cash']) }}</td>
                            </tr>

                            <tr class="table-light"><th colspan="4">Reconciliation</th></tr>
                            <tr>
                                <td class="ps-4">Opening balance + net cash movement</td>
                                <td></td><td></td>
                                <td class="text-end">{{ currency($result['reconciled_closing']) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Actual closing cash &amp; bank balance</td>
                                <td></td><td></td>
                                <td class="text-end">{{ currency($result['closing_cash']) }}</td>
                            </tr>
                            <tr class="fw-bold {{ abs($result['reconciliation_difference']) > 0.009 ? 'table-warning' : '' }}">
                                <td>Difference</td>
                                <td></td><td></td>
                                <td class="text-end">{{ currency($result['reconciliation_difference']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @endif
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
            window.open(buildReportUrl('/admin/reports/cash-flow/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/cash-flow/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/cash-flow/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/cash-flow/export-csv');
        });
    </script>
@endsection
