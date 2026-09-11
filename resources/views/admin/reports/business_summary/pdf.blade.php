@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
    $meta = $result['meta'] ?? [];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 14px; }
        table.data-table th, table.data-table td { border: 1px solid #ccc; padding: 4px 6px; }
        table.data-table th { background-color: #f2f2f2; text-align: left; }
        .text-right { text-align: right; }
        h4 { margin: 14px 0 6px; font-size: 13px; }
        .muted { color: #666; font-size: 10px; }
    </style>
</head>
<body>
    @include('admin.partials.print.pdf_header', [
        'business' => $business,
        'branch' => null,
        'title' => $meta['title'] ?? __('reports.business_summary_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [
            __('reports.business_summary.col_period') => localDate($meta['start_date'] ?? null) . ' — ' . localDate($meta['end_date'] ?? null),
        ],
        'print_config' => $print_config,
    ])

    <p class="muted">{{ $meta['business_name'] ?? '' }} · {{ $meta['generated_at'] ?? '' }} · {{ $meta['timezone'] ?? '' }}</p>

    <h4>{{ __('reports.business_summary.executive_overview') }}</h4>
    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.business_summary.col_kpi') }}</th>
                <th class="text-right">{{ __('reports.business_summary.col_value') }}</th>
                <th class="text-right">{{ __('reports.business_summary.col_change') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($result['executive_overview'] ?? [] as $card)
                <tr>
                    <td>{{ $card['label'] }}</td>
                    <td class="text-right">{{ $card['value'] }}</td>
                    <td class="text-right">{{ $card['delta'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @foreach (['critical', 'important', 'informational', 'good', 'excellent'] as $section)
        <h4>{{ __('reports.business_summary.section_' . $section) }}</h4>
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('reports.business_summary.col_area') }}</th>
                    <th>{{ __('reports.business_summary.col_insight') }}</th>
                    <th>{{ __('reports.business_summary.col_description') }}</th>
                    <th>{{ __('reports.business_summary.col_details') }}</th>
                    <th class="text-right">{{ __('reports.business_summary.col_metric') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($result[$section] ?? [] as $item)
                    <tr>
                        <td>{{ __('reports.business_summary.area_' . str_replace('service-management', 'services', $item['module'] ?? 'general')) }}</td>
                        <td>{{ $item['title'] }}</td>
                        <td>
                            {{ $item['description'] ?? '' }}
                            @if (!empty($item['why']))
                                <br><em>{{ $item['why'] }}</em>
                            @endif
                        </td>
                        <td>
                            @foreach ($item['details'] ?? [] as $detail)
                                {{ $detail['label'] ?? '' }}: {{ $detail['value'] ?? '' }}{{ !empty($detail['extra']) ? ' ' . $detail['extra'] : '' }}@if (!$loop->last)<br>@endif
                            @endforeach
                        </td>
                        <td class="text-right">{{ $item['metric_formatted'] ?? $item['value_formatted'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">{{ __('reports.business_summary.section_empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
