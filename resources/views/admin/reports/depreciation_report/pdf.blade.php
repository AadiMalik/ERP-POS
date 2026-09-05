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
        'title' => __('reports.depreciation_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])
    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_date') }}</th>
                <th>{{ __('reports.col_period') }}</th>
                <th>{{ __('reports.col_asset') }}</th>
                <th>{{ __('reports.col_branch') }}</th>
                <th class="text-right">Previous</th>
                <th class="text-right">{{ __('reports.col_amount') }}</th>
                <th class="text-right">New Value</th>
                <th class="text-right">Accumulated</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->depreciation_date ? localDate($row->depreciation_date) : '' }}</td>
                    <td>{{ $row->period_key }}</td>
                    <td>{{ $row->asset_code }} {{ $row->asset_name }}</td>
                    <td>{{ $row->branch }}</td>
                    <td class="text-right">{{ currency($row->previous_value) }}</td>
                    <td class="text-right">{{ currency($row->depreciation_amount) }}</td>
                    <td class="text-right">{{ currency($row->new_value) }}</td>
                    <td class="text-right">{{ currency($row->accumulated_depreciation) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center">No records found</td></tr>
            @endforelse
            <tr>
                <td colspan="5"><strong>Total Depreciation</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('depreciation_amount')) }}</strong></td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
