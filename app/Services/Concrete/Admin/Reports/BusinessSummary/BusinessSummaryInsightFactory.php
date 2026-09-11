<?php

namespace App\Services\Concrete\Admin\Reports\BusinessSummary;

/**
 * Builds a presentation-neutral insight array. Titles/descriptions are
 * translated at build time so Blade, PDF, Excel and JSON all share the
 * current locale; keys stay on the payload for tests and future reuse.
 */
class BusinessSummaryInsightFactory
{
    public static function make(array $attrs): array
    {
        $titleParams = $attrs['title_params'] ?? [];
        $descriptionKey = $attrs['description_key'] ?? null;
        $descriptionParams = $attrs['description_params'] ?? [];
        $whyKey = $attrs['why_key'] ?? null;
        $whyParams = $attrs['why_params'] ?? [];

        $metric = $attrs['metric'] ?? null;
        $value = $attrs['value'] ?? null;

        return [
            'id' => $attrs['id'],
            'module' => $attrs['module'] ?? 'general',
            'severity' => $attrs['severity'],
            'icon' => $attrs['icon'] ?? 'fa-circle-info',
            'title_key' => $attrs['title_key'],
            'title' => __($attrs['title_key'], $titleParams),
            'description_key' => $descriptionKey,
            'description' => $descriptionKey ? __($descriptionKey, $descriptionParams) : null,
            'why_key' => $whyKey,
            'why' => $whyKey ? __($whyKey, $whyParams) : null,
            'metric' => $metric,
            'metric_type' => $attrs['metric_type'] ?? 'count',
            'metric_formatted' => $attrs['metric_formatted'] ?? self::formatMetric($metric, $attrs['metric_type'] ?? 'count'),
            'value' => $value,
            'value_formatted' => $attrs['value_formatted'] ?? ($value !== null ? currency($value) : null),
            'impact' => (float) ($attrs['impact'] ?? $value ?? $metric ?? 0),
            'delta_percent' => $attrs['delta_percent'] ?? null,
            'details' => $attrs['details'] ?? [],
            'action' => $attrs['action'] ?? null,
            'empty' => (bool) ($attrs['empty'] ?? false),
        ];
    }

    public static function action(BusinessSummaryContext $context, string $labelKey, string $path, array $extra = [], ?string $permission = null): ?array
    {
        if ($permission && !$context->can($permission)) {
            return null;
        }

        return [
            'label_key' => $labelKey,
            'label' => __($labelKey),
            'url' => $context->actionUrl($path, $extra),
        ];
    }

    public static function percentChange($current, $previous): ?float
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : ($current < 0 ? -100.0 : 0.0);
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    public static function formatMetric($metric, string $type): ?string
    {
        if ($metric === null) {
            return null;
        }

        return match ($type) {
            'money' => currency($metric),
            'percent' => rtrim(rtrim(number_format((float) $metric, 1, '.', ''), '0'), '.') . '%',
            default => is_float($metric)
                ? rtrim(rtrim(number_format($metric, 3, '.', ''), '0'), '.')
                : (string) $metric,
        };
    }
}
