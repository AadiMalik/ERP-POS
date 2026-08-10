{{--
    Shared print letterhead partial.
    Expects: $business, $branch (nullable), $title, $doc_no, $doc_date, $reference (assoc array, optional)
    Optional: $print_config (App\Support\Print\PrintConfig) - when absent, renders exactly as the
    original hardcoded layout (defensive fallback, see PrintConfig::isVisible()).
--}}
@php
    $pc = $print_config ?? new \App\Support\Print\PrintConfig(config('print_defaults'));

    $left_fields = $pc->orderedHeaderFields('left');
    $right_fields = $pc->orderedHeaderFields('right');
@endphp

@if ($pc->watermark()['visible'] ?? false)
    @php $wm = $pc->watermark(); @endphp
    <div class="print-watermark"
        style="color: {{ $wm['color'] ?? '#cccccc' }}; opacity: {{ $wm['opacity'] ?? 0.15 }}; font-size: {{ $wm['font_size'] ?? 60 }}px; transform: translate(-50%, -50%) rotate({{ $wm['rotation_deg'] ?? -30 }}deg);">
        {{ $wm['text'] ?? 'DRAFT' }}
    </div>
@endif

<div class="print-header">
    <div class="company-block">
        @if ($pc->isVisible('header', 'logo'))
            <img class="company-logo"
                src="{{ !empty($business->logo) ? asset('public/uploads/business/' . $business->logo) : asset('public/assets/img/no-image.png') }}"
                alt="Logo">
        @endif
        <div>
            @foreach ($left_fields as $field)
                @php $style = $pc->fieldStyle($field); @endphp
                @if ($pc->isVisible('header', $field))
                    @switch($field)
                        @case('company_name')
                            <p class="company-name"
                                style="font-size:{{ $style['font_size'] ?? 16 }}px; font-weight:{{ $style['font_weight'] ?? 'bold' }}; color:{{ $style['color'] ?? '#1a1a1a' }};">
                                {{ $business->name ?? 'N/A' }}
                            </p>
                        @break

                        @case('branch_name')
                            @if (!empty($branch))
                                <p class="branch-meta"
                                    style="font-size:{{ $style['font_size'] ?? 11 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    <strong>Branch:</strong> {{ $branch->name }}
                                    @if (!empty($branch->address))
                                        - {{ $branch->address }}
                                    @endif
                                </p>
                            @endif
                        @break

                        @case('address')
                            <p class="company-meta"
                                style="font-size:{{ $style['font_size'] ?? 11 }}px; color:{{ $style['color'] ?? '#444' }};">
                                {{ collect([$business->address ?? null, $business->city ?? null, $business->state ?? null, $business->country ?? null])->filter()->implode(', ') }}
                            </p>
                        @break

                        @case('phone')
                            @if (!empty($business->phone))
                                <p class="company-meta"
                                    style="font-size:{{ $style['font_size'] ?? 11 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    Tel: {{ $business->phone }}
                                </p>
                            @endif
                        @break

                        @case('email')
                            @if (!empty($business->email))
                                <p class="company-meta"
                                    style="font-size:{{ $style['font_size'] ?? 11 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    Email: {{ $business->email }}
                                </p>
                            @endif
                        @break

                        @case('website')
                            @if (!empty($business->website))
                                <p class="company-meta"
                                    style="font-size:{{ $style['font_size'] ?? 11 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    {{ $business->website }}
                                </p>
                            @endif
                        @break

                        @case('ntn')
                            @if (!empty($business->ntn))
                                <p class="company-meta"
                                    style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    NTN: {{ $business->ntn }}
                                </p>
                            @endif
                        @break

                        @case('strn')
                            @if (!empty($business->strn))
                                <p class="company-meta"
                                    style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    STRN: {{ $business->strn }}
                                </p>
                            @endif
                        @break

                        @case('tax_reg_no')
                            @if (!empty($business->tax_reg_no))
                                <p class="company-meta"
                                    style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    Tax Reg. No: {{ $business->tax_reg_no }}
                                </p>
                            @endif
                        @break

                        @case('currency')
                            <p class="company-meta"
                                style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                Currency: {{ session('accounting_setting.currency_symbol', 'Rs') }}
                            </p>
                        @break
                    @endswitch
                @endif
            @endforeach
        </div>
    </div>

    <div class="doc-block">
        @foreach ($right_fields as $field)
            @php $style = $pc->fieldStyle($field); @endphp
            @if ($pc->isVisible('header', $field))
                @switch($field)
                    @case('document_title')
                        <p class="doc-title"
                            style="font-size:{{ $style['font_size'] ?? 18 }}px; font-weight:{{ $style['font_weight'] ?? 'bold' }}; color:{{ $style['color'] ?? '#1a1a1a' }};">
                            {{ $title }}
                        </p>
                    @break

                    @case('document_no')
                        <p class="doc-meta"
                            style="font-size:{{ $style['font_size'] ?? 11 }}px; color:{{ $style['color'] ?? '#1a1a1a' }};">
                            <strong>Document No:</strong> {{ $doc_no ?? 'N/A' }}
                        </p>
                    @break

                    @case('date')
                        <p class="doc-meta"
                            style="font-size:{{ $style['font_size'] ?? 11 }}px; color:{{ $style['color'] ?? '#1a1a1a' }};">
                            <strong>Date:</strong> {{ $doc_date ?? 'N/A' }}
                        </p>
                    @break

                    {{-- 'voucher_no', 'reference_no' and 'time' have no single cross-module data
                         source today (each module supplies its own $reference array below) -
                         they are schema-complete toggles reserved for a future data hookup. --}}
                @endswitch
            @endif
        @endforeach

        @foreach ($reference ?? [] as $label => $value)
            <p class="doc-meta"><strong>{{ $label }}:</strong> {{ $value ?? 'N/A' }}</p>
        @endforeach
    </div>
</div>

@php
    $show_printed_by = $pc->isVisible('header', 'printed_by');
    $show_printed_on = $pc->isVisible('header', 'printed_on');
@endphp
@if ($show_printed_by || $show_printed_on)
    <p class="printed-by">
        @if ($show_printed_by)
            Printed by {{ Auth::user()->name ?? 'N/A' }}
        @endif
        @if ($show_printed_on)
            on {{ localDateTime(now()) }}
        @endif
    </p>
@endif
