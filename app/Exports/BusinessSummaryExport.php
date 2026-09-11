<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class BusinessSummaryExport implements FromArray, ShouldAutoSize, WithTitle
{
    public function __construct(protected array $result)
    {
    }

    public function title(): string
    {
        return __('reports.business_summary_report');
    }

    public function array(): array
    {
        $meta = $this->result['meta'] ?? [];
        $rows = [
            [$meta['title'] ?? __('reports.business_summary_report')],
            [__('reports.business_summary.col_business'), $meta['business_name'] ?? ''],
            [__('reports.business_summary.col_period'), ($meta['start_date'] ?? '') . ' — ' . ($meta['end_date'] ?? '')],
            [__('reports.business_summary.col_generated'), $meta['generated_at'] ?? ''],
            [],
            [__('reports.business_summary.executive_overview')],
            [__('reports.business_summary.col_kpi'), __('reports.business_summary.col_value'), __('reports.business_summary.col_change')],
        ];

        foreach ($this->result['executive_overview'] ?? [] as $card) {
            $rows[] = [$card['label'] ?? '', $card['value'] ?? '', $card['delta'] ?? ''];
        }

        foreach (['critical', 'important', 'informational', 'good', 'excellent'] as $section) {
            $items = $this->result[$section] ?? [];
            $rows[] = [];
            $rows[] = [__('reports.business_summary.section_' . $section)];
            $rows[] = [
                __('reports.business_summary.col_area'),
                __('reports.business_summary.col_insight'),
                __('reports.business_summary.col_description'),
                __('reports.business_summary.col_details'),
                __('reports.business_summary.col_metric'),
                __('reports.business_summary.col_why'),
            ];

            if ($items === []) {
                $rows[] = [__('reports.business_summary.section_empty')];
                continue;
            }

            foreach ($items as $item) {
                $rows[] = [
                    $this->areaLabel($item),
                    $item['title'] ?? '',
                    $item['description'] ?? '',
                    $this->detailsText($item),
                    $item['metric_formatted'] ?? ($item['value_formatted'] ?? ''),
                    $item['why'] ?? '',
                ];
            }
        }

        return $rows;
    }

    protected function areaLabel(array $item): string
    {
        $map = [
            'sales' => 'reports.business_summary.area_sales',
            'inventory' => 'reports.business_summary.area_inventory',
            'purchasing' => 'reports.business_summary.area_purchasing',
            'accounting' => 'reports.business_summary.area_accounting',
            'hrm' => 'reports.business_summary.area_hrm',
            'manufacturing' => 'reports.business_summary.area_manufacturing',
            'service-management' => 'reports.business_summary.area_services',
            'general' => 'reports.business_summary.area_general',
        ];

        return __($map[$item['module'] ?? 'general'] ?? $map['general']);
    }

    protected function detailsText(array $item): string
    {
        $parts = [];
        foreach ($item['details'] ?? [] as $detail) {
            $line = trim(($detail['label'] ?? '') . ': ' . ($detail['value'] ?? ''));
            if (!empty($detail['extra'])) {
                $line .= ' ' . $detail['extra'];
            }
            if ($line !== ':') {
                $parts[] = $line;
            }
        }

        return implode(' | ', $parts);
    }
}
