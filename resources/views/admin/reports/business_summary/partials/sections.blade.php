@php
    $sectionMeta = [
        'critical' => ['icon' => 'fa-triangle-exclamation', 'tone' => 'danger'],
        'important' => ['icon' => 'fa-circle-exclamation', 'tone' => 'warning'],
        'informational' => ['icon' => 'fa-circle-info', 'tone' => 'info'],
        'good' => ['icon' => 'fa-thumbs-up', 'tone' => 'success'],
        'excellent' => ['icon' => 'fa-star', 'tone' => 'primary'],
    ];
    $moduleOrder = ['sales', 'inventory', 'purchasing', 'accounting', 'hrm', 'manufacturing', 'service-management', 'general'];
    $moduleKeys = [
        'sales' => 'area_sales',
        'inventory' => 'area_inventory',
        'purchasing' => 'area_purchasing',
        'accounting' => 'area_accounting',
        'hrm' => 'area_hrm',
        'manufacturing' => 'area_manufacturing',
        'service-management' => 'area_services',
        'general' => 'area_general',
    ];
@endphp

<div class="accordion bs-faq" id="bsSectionFaq">
    @foreach ($sectionMeta as $section => $meta)
        @php
            $items = $result[$section] ?? [];
            $grouped = [];
            foreach ($items as $item) {
                $mod = $item['module'] ?? 'general';
                $grouped[$mod][] = $item;
            }
            $orderedModules = array_values(array_unique(array_merge(
                array_values(array_intersect($moduleOrder, array_keys($grouped))),
                array_keys($grouped)
            )));
            $open = $section === 'critical';
        @endphp
        <div class="accordion-item bs-faq-item bs-faq-item--{{ $section }} mb-3" id="bs-section-{{ $section }}">
            <h2 class="accordion-header" id="bs-head-{{ $section }}">
                <button class="accordion-button {{ $open ? '' : 'collapsed' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#bs-body-{{ $section }}"
                    aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="bs-body-{{ $section }}">
                    <span class="d-flex align-items-center justify-content-between w-100 me-2 gap-2">
                        <span class="d-flex align-items-center gap-2">
                            <i class="fa {{ $meta['icon'] }}"></i>
                            {{ __('reports.business_summary.section_' . $section) }}
                        </span>
                        <span class="badge bg-dark">{{ count($items) }}</span>
                    </span>
                </button>
            </h2>
            <div id="bs-body-{{ $section }}" class="accordion-collapse collapse {{ $open ? 'show' : '' }}"
                aria-labelledby="bs-head-{{ $section }}">
                <div class="accordion-body">
                    @if (empty($items))
                        <div class="text-muted">{{ __('reports.business_summary.section_empty') }}</div>
                    @else
                        @foreach ($orderedModules as $mod)
                            @php $rows = $grouped[$mod] ?? []; @endphp
                            @continue(empty($rows))
                            <div class="bs-faq-area">{{ __('reports.business_summary.' . ($moduleKeys[$mod] ?? 'area_general')) }}</div>
                            <ul class="bs-faq-list">
                                @foreach ($rows as $item)
                                    <li>
                                        <span class="bs-dot" aria-hidden="true"></span>
                                        <div class="bs-faq-copy">
                                            <div>
                                                @if (!empty($item['action']['url']))
                                                    <a href="{{ $item['action']['url'] }}">{{ $item['title'] }}</a>
                                                @else
                                                    <strong>{{ $item['title'] }}</strong>
                                                @endif
                                                @if (!empty($item['metric_formatted']))
                                                    <span class="text-muted"> — {{ $item['metric_formatted'] }}</span>
                                                @endif
                                                @if (isset($item['delta_percent']) && $item['delta_percent'] !== null)
                                                    <span class="{{ $item['delta_percent'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                        ({{ ($item['delta_percent'] > 0 ? '+' : '') . number_format((float) $item['delta_percent'], 1) }}%)
                                                    </span>
                                                @endif
                                            </div>
                                            @if (!empty($item['description']))
                                                <div class="text-muted small">{{ $item['description'] }}</div>
                                            @endif
                                            @if (!empty($item['why']))
                                                <div class="small">{{ $item['why'] }}</div>
                                            @endif
                                            @if (!empty($item['details']))
                                                <ul class="bs-faq-sub">
                                                    @foreach ($item['details'] as $detail)
                                                        <li>
                                                            {{ $detail['label'] ?? '' }}
                                                            @if (!empty($detail['value']))
                                                                : {{ $detail['value'] }}
                                                            @endif
                                                            @if (!empty($detail['extra']))
                                                                · {{ $detail['extra'] }}
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                            @if (!empty($item['action']['url']))
                                                <a href="{{ $item['action']['url'] }}" class="small">{{ $item['action']['label'] }}</a>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
