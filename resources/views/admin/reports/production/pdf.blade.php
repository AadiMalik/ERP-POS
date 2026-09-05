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
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #ccc; padding: 4px 6px; }
        table.data-table th { background-color: #f2f2f2; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    @include('admin.partials.print.pdf_header', [
        'business' => $business, 'branch' => null, 'title' => __('reports.production'),
        'doc_no' => '', 'doc_date' => localDate(now()), 'reference' => [], 'print_config' => $print_config,
    ])
    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_production_no') }}</th><th>{{ __('reports.col_plan_no') }}</th><th>{{ __('reports.col_product') }}</th><th>{{ __('reports.col_warehouse') }}</th><th>{{ __('reports.col_batch') }}</th>
                <th>{{ __('reports.col_mfg_date_dot') }}</th><th>{{ __('reports.col_expiry') }}</th><th class="text-right">{{ __('reports.col_qty') }}</th>
                <th class="text-right">Material Cost</th><th class="text-right">Labor</th>
                <th class="text-right">Overhead</th><th class="text-right">Total Cost</th>
                <th class="text-right">{{ __('reports.col_unit_cost') }}</th><th>{{ __('reports.col_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->production_no }}</td>
                    <td>{{ $row->plan_no }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->warehouse_name }}</td>
                    <td>{{ $row->batch_no }}</td>
                    <td>{{ $row->manufacturing_date }}</td>
                    <td>{{ $row->expiry_date }}</td>
                    <td class="text-right">{{ decimal($row->quantity) }}</td>
                    <td class="text-right">{{ currency($row->material_cost) }}</td>
                    <td class="text-right">{{ currency($row->labor_cost) }}</td>
                    <td class="text-right">{{ currency($row->overhead_cost + $row->other_cost) }}</td>
                    <td class="text-right">{{ currency($row->total_cost) }}</td>
                    <td class="text-right">{{ currency($row->unit_cost) }}</td>
                    <td>{{ $row->status_label }}</td>
                </tr>
            @empty
                <tr><td colspan="14" class="text-center">No records found</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
