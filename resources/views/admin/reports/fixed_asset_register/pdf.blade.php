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
        table.data-table th, table.data-table td { border: 1px solid #ccc; padding: 3px 5px; }
        table.data-table th { background-color: #f2f2f2; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    @include('admin.partials.print.pdf_header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.fixed_asset_register'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])
    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_code') }}</th>
                <th>{{ __('reports.col_name') }}</th>
                <th>{{ __('reports.col_category') }}</th>
                <th>{{ __('reports.col_branch') }}</th>
                <th>{{ __('reports.col_purchase_date') }}</th>
                <th class="text-right">Cost</th>
                <th class="text-right">Book Value</th>
                <th class="text-right">Accum. Dep.</th>
                <th>{{ __('reports.col_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->asset_code }}</td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->category }}</td>
                    <td>{{ $row->branch }}</td>
                    <td>{{ $row->purchase_date ? localDate($row->purchase_date) : '' }}</td>
                    <td class="text-right">{{ currency($row->purchase_cost) }}</td>
                    <td class="text-right">{{ currency($row->current_book_value) }}</td>
                    <td class="text-right">{{ currency($row->accumulated_depreciation) }}</td>
                    <td>{{ $row->depreciation_status }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center">No records found</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
