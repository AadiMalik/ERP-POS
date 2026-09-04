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
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #ccc; padding: 4px 6px; }
        table.data-table th { background-color: #f2f2f2; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    @include('admin.partials.print.pdf_header', [
        'business' => $business, 'branch' => null, 'title' => 'Material Consumption Report',
        'doc_no' => '', 'doc_date' => localDate(now()), 'reference' => [], 'print_config' => $print_config,
    ])
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th><th>Raw Material</th><th>Batch Consumed</th><th class="text-right">Qty</th>
                <th class="text-right">Unit Cost</th><th class="text-right">Total Cost</th><th>Warehouse</th><th>Production #</th><th>Plan #</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ localDate($row->date_created) }}</td>
                    <td>{{ $row->raw_material_name }}</td>
                    <td>{{ $row->batch_no }}</td>
                    <td class="text-right">{{ decimal($row->base_quantity) }}</td>
                    <td class="text-right">{{ currency($row->unit_cost) }}</td>
                    <td class="text-right">{{ currency($row->total_cost) }}</td>
                    <td>{{ $row->warehouse_name }}</td>
                    <td>{{ $row->production_no }}</td>
                    <td>{{ $row->plan_no }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center">No records found</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
