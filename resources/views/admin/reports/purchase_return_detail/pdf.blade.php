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
        'title' => 'Purchase Return Detail Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>Return Date</th>
                <th>Return No.</th>
                <th>Source Ref.</th>
                <th>Supplier</th>
                <th>Warehouse</th>
                <th>Product</th>
                <th>Variation</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ localDate($row->purchase_return_date) }}</td>
                    <td>{{ $row->purchase_return_no }}</td>
                    <td>{{ ($row->return_type === 'grn' ? 'GRN: ' : 'Purchase: ') . ($row->source_no ?? '') }}</td>
                    <td>{{ $row->supplier_name }}</td>
                    <td>{{ $row->warehouse_name }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->variation_name }}</td>
                    <td class="text-right">{{ decimal($row->return_quantity) }} {{ $row->unit_name }}</td>
                    <td class="text-right">{{ currency($row->unit_price) }}</td>
                    <td class="text-right">{{ currency($row->discount_amount) }}</td>
                    <td class="text-right">{{ currency($row->tax_amount) }}</td>
                    <td class="text-right">{{ currency($row->total) }}</td>
                    <td>{{ $row->status_label }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="text-center">No records found</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="7"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ decimal($rows->sum('return_quantity')) }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ currency($rows->sum('discount_amount')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('tax_amount')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('total')) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
