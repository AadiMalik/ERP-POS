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
            font-size: 11px;
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
            padding: 4px 6px;
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
        'title' => 'Stock Ledger & Movement Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Source Module</th>
                <th>Reference No.</th>
                <th>Warehouse</th>
                <th>Product</th>
                <th>Variation</th>
                <th>Movement Type</th>
                <th class="text-right">Qty In</th>
                <th class="text-right">Qty Out</th>
                <th class="text-right">Unit Cost</th>
                <th class="text-right">Value</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ localDate($row->transaction_date) }}</td>
                    <td>{{ $row->source_module }}</td>
                    <td>{{ $row->reference_no }}</td>
                    <td>{{ $row->warehouse_name }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->variation_name }}</td>
                    <td>{{ $row->transaction_type_label }}</td>
                    <td class="text-right">{{ $row->quantity_in > 0 ? decimal($row->quantity_in) : '' }}</td>
                    <td class="text-right">{{ $row->quantity_out > 0 ? decimal($row->quantity_out) : '' }}</td>
                    <td class="text-right">{{ currency($row->unit_price) }}</td>
                    <td class="text-right">{{ currency($row->value) }}</td>
                    <td class="text-right">{{ decimal($row->quantity_after) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center">No records found</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="7"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ decimal($rows->sum('quantity_in')) }}</strong></td>
                <td class="text-right"><strong>{{ decimal($rows->sum('quantity_out')) }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ currency($rows->sum('value')) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
