@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
    $meta = $result['meta'] ?? [];
@endphp
@extends('layouts.print')

@section('title', $meta['title'] ?? __('reports.business_summary_report'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
    <style>
        .bs-kpi-table td { padding: 4px 8px; }
        .bs-section-title { margin-top: 16px; font-size: 13px; text-transform: uppercase; }
        .bs-insight-row td { vertical-align: top; }
    </style>
@endsection

@section('content')
    @include('admin.partials.print.header', [
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

    <h4>{{ __('reports.business_summary.executive_overview') }}</h4>
    <table class="print-table bs-kpi-table">
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
        <h4 class="bs-section-title">{{ __('reports.business_summary.section_' . $section) }}</h4>
        <table class="print-table">
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
                    <tr class="bs-insight-row">
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

    @include('admin.partials.print.footer', [
        'signatories' => [],
        'print_config' => $print_config,
    ])
@endsection
