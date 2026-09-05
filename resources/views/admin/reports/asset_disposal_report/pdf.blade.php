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
        'title' => __('reports.asset_disposal_report'),
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
                <th>{{ __('reports.col_disposal_date') }}</th>
                <th>{{ __('reports.col_type') }}</th>
                <th class="text-right">Sale Price</th>
                <th class="text-right">Book Value</th>
                <th>{{ __('reports.col_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->asset_code }}</td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->category }}</td>
                    <td>{{ $row->disposal_date ? localDate($row->disposal_date) : '' }}</td>
                    <td>{{ $row->disposal_type }}</td>
                    <td class="text-right">{{ currency($row->sale_price) }}</td>
                    <td class="text-right">{{ currency($row->current_book_value) }}</td>
                    <td>{{ $row->depreciation_status }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center">No records found</td></tr>
            @endforelse
            <tr>
                <td colspan="5"><strong>Totals</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('sale_price')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('current_book_value')) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
