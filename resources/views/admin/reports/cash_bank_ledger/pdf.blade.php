@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #ccc;
            padding: 3px 5px;
        }

        table.data-table th {
            background-color: #f2f2f2;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary-row {
            font-weight: bold;
            background-color: #f5f5f5;
        }
    </style>
</head>

<body>
    @include('admin.partials.print.pdf_header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.cash_bank_ledger'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_account') }}</th>
                <th>{{ __('reports.col_date') }}</th>
                <th>{{ __('reports.col_voucher_type') }}</th>
                <th>{{ __('reports.col_jv_number') }}</th>
                <th>{{ __('reports.col_reference') }}</th>
                <th>{{ __('reports.col_narration') }}</th>
                <th class="text-right">Receipt</th>
                <th class="text-right">Payment</th>
                <th class="text-right">{{ __('reports.col_balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($result['rows'] as $row)
                <tr @if (!empty($row->is_summary)) class="summary-row" @endif>
                    <td>{{ trim(($row->account_code ?? '') . ' ' . ($row->account_name ?? '')) }}</td>
                    <td>{{ $row->entry_date ? localDate($row->entry_date) : '' }}</td>
                    <td>{{ $row->voucher_name ?? $row->source_type }}</td>
                    <td>{{ $row->entry_no }}</td>
                    <td>{{ $row->reference_no }}</td>
                    <td>{{ $row->detail_description ?: $row->entry_description }}</td>
                    <td class="text-right">{{ $row->debit > 0 ? currency($row->debit) : '' }}</td>
                    <td class="text-right">{{ $row->credit > 0 ? currency($row->credit) : '' }}</td>
                    <td class="text-right">{{ currency($row->running_balance) }} {{ $row->running_balance_type }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No records found</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="6"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ currency($result['total_receipts']) }}</strong></td>
                <td class="text-right"><strong>{{ currency($result['total_payments']) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
