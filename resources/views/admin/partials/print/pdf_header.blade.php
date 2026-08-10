{{--
    dompdf-safe letterhead partial (table-based layout, no flexbox).
    Expects: $business, $branch (nullable), $title, $doc_no, $doc_date, $reference (assoc array, optional)
    Optional: $print_config (App\Support\Print\PrintConfig)
--}}
@php
    $pc = $print_config ?? new \App\Support\Print\PrintConfig(config('print_defaults'));

    $left_fields = $pc->orderedHeaderFields('left');
    $right_fields = $pc->orderedHeaderFields('right');
@endphp
<table style="width:100%; border-bottom:2px solid #333; margin-bottom:12px;">
    <tr>
        <td style="width:60%; vertical-align:top;">
            @foreach ($left_fields as $field)
                @php $style = $pc->fieldStyle($field); @endphp
                @if ($pc->isVisible('header', $field))
                    @switch($field)
                        @case('logo')
                            <img
                                src="{{ !empty($business->logo) ? asset('public/uploads/business/' . $business->logo) : asset('public/assets/img/no-image.png') }}"
                                style="max-width:{{ $pc->page('logo_max_width_px', 60) }}px; max-height:{{ $pc->page('logo_max_height_px', 60) }}px; margin-bottom:4px;"
                                alt="Logo">
                        @break

                        @case('company_name')
                            <div
                                style="font-size:{{ $style['font_size'] ?? 16 }}px; font-weight:{{ $style['font_weight'] ?? 'bold' }}; color:{{ $style['color'] ?? '#1a1a1a' }};">
                                {{ $business->name ?? 'N/A' }}
                            </div>
                        @break

                        @case('branch_name')
                            @if (!empty($branch))
                                <div style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    <strong>Branch:</strong> {{ $branch->name }}
                                </div>
                            @endif
                        @break

                        @case('address')
                            <div style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                {{ collect([$business->address ?? null, $business->city ?? null, $business->state ?? null, $business->country ?? null])->filter()->implode(', ') }}
                            </div>
                        @break

                        @case('phone')
                            @if (!empty($business->phone))
                                <div style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    Tel: {{ $business->phone }}
                                </div>
                            @endif
                        @break

                        @case('email')
                            @if (!empty($business->email))
                                <div style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    Email: {{ $business->email }}
                                </div>
                            @endif
                        @break

                        @case('website')
                            @if (!empty($business->website))
                                <div style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    {{ $business->website }}
                                </div>
                            @endif
                        @break

                        @case('ntn')
                            @if (!empty($business->ntn))
                                <div style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    NTN: {{ $business->ntn }}
                                </div>
                            @endif
                        @break

                        @case('strn')
                            @if (!empty($business->strn))
                                <div style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    STRN: {{ $business->strn }}
                                </div>
                            @endif
                        @break

                        @case('tax_reg_no')
                            @if (!empty($business->tax_reg_no))
                                <div style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    Tax Reg. No: {{ $business->tax_reg_no }}
                                </div>
                            @endif
                        @break

                        @case('currency')
                            <div style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                Currency: {{ session('accounting_setting.currency_symbol', 'Rs') }}
                            </div>
                        @break
                    @endswitch
                @endif
            @endforeach
        </td>
        <td style="width:40%; vertical-align:top; text-align:right;">
            @foreach ($right_fields as $field)
                @php $style = $pc->fieldStyle($field); @endphp
                @if ($pc->isVisible('header', $field))
                    @switch($field)
                        @case('document_title')
                            <div
                                style="font-size:{{ $style['font_size'] ?? 14 }}px; font-weight:{{ $style['font_weight'] ?? 'bold' }}; color:{{ $style['color'] ?? '#1a1a1a' }};">
                                {{ $title }}
                            </div>
                        @break

                        @case('document_no')
                            <div style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#1a1a1a' }};">
                                <strong>Document No:</strong> {{ $doc_no ?? 'N/A' }}
                            </div>
                        @break

                        @case('date')
                            <div style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#1a1a1a' }};">
                                <strong>Date:</strong> {{ $doc_date ?? 'N/A' }}
                            </div>
                        @break
                    @endswitch
                @endif
            @endforeach

            @foreach ($reference ?? [] as $label => $value)
                <div style="font-size:10px;"><strong>{{ $label }}:</strong> {{ $value ?? 'N/A' }}</div>
            @endforeach
        </td>
    </tr>
</table>
