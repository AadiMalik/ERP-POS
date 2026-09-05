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
            padding: 4px 5px;
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
    </style>
</head>

<body>
    @include('admin.partials.print.pdf_header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.supplier_payment_history'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_payment_date') }}</th>
                <th>{{ __('reports.col_payment_no') }}</th>
                <th>{{ __('reports.col_supplier') }}</th>
                <th>{{ __('reports.col_method') }}</th>
                <th>{{ __('reports.col_ref_purchase') }}</th>
                <th>{{ __('reports.col_bank_cash_account') }}</th>
                <th class="text-right">{{ __('reports.col_tax') }}</th>
                <th class="text-right">{{ __('reports.col_discount') }}</th>
                <th class="text-right">Net Payment</th>
                <th>{{ __('reports.col_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ localDate($row->payment_date) }}</td>
                    <td>{{ $row->payment_no }}</td>
                    <td>{{ $row->supplier->name ?? '' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $row->payment_method)) }}</td>
                    <td>{{ $row->purchase->purchase_no ?? '' }}</td>
                    <td>{{ $row->paymentAccount->name ?? '' }}</td>
                    <td class="text-right">{{ currency($row->tax_amount) }}</td>
                    <td class="text-right">{{ currency($row->discount_amount) }}</td>
                    <td class="text-right">{{ currency($row->net_amount) }}</td>
                    <td>{{ ucfirst($row->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No payments found</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="6"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('tax_amount')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('discount_amount')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('net_amount')) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
