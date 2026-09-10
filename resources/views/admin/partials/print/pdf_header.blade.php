{{--
    dompdf-safe letterhead partial (table-based layout, no flexbox).
    Expects: $business, $branch (nullable), $title, $doc_no, $doc_date, $reference (assoc array, optional)
    Optional: $print_config (App\Support\Print\PrintConfig)
--}}
@php
    $pc = $print_config ?? new \App\Support\Print\PrintConfig(config('print_defaults'));

    $left_fields = $pc->orderedHeaderFields('left');
    $right_fields = $pc->orderedHeaderFields('right');

    // dompdf can't load the external print.css templates (and can't safely invert
    // per-field text colors that admins configure independently), so each of the
    // 8 designs is reproduced here as a distinct dompdf-safe border/background
    // treatment on the wrapping table - same idea as the browser templates, just
    // without the flexbox re-layout (elegant/compact) or dark reversed panels
    // (modern/corporate) that dompdf's table model can't reliably reproduce.
    $template_table_style = [
        'modern'    => 'border:none; border-top:3px solid #3833C8; border-bottom:3px solid #3833C8; padding:8px 0;',
        'minimal'   => 'border-bottom:none;',
        'boxed'     => 'border:2px solid #1a1a1a; padding:10px;',
        'elegant'   => 'border-bottom:1px solid #999; padding-bottom:10px;',
        'corporate' => 'background:#f4f5fb; border:none; border-left:4px solid #1a1a2e; padding:10px;',
        'bold'      => 'border:none; border-bottom:5px double #1a1a1a; padding-bottom:8px;',
        'compact'   => 'border-bottom:1px solid #ccc; padding-bottom:4px;',
    ][$pc->headerTemplate()] ?? '';
@endphp
<table style="width:100%; border-bottom:2px solid #333; margin-bottom:12px; {{ $template_table_style }}">
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
                            @if (!empty(optional($business->fbrSetting)->fbr_ntn))
                                <div style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    NTN: {{ $business->fbrSetting->fbr_ntn }}
                                </div>
                            @endif
                        @break

                        @case('strn')
                            @if (!empty(optional($business->fbrSetting)->fbr_strn))
                                <div style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    STRN: {{ $business->fbrSetting->fbr_strn }}
                                </div>
                            @endif
                        @break

                        @case('tax_reg_no')
                            @if (!empty(optional($business->praSetting)->pra_registration_no))
                                <div style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#444' }};">
                                    Tax Reg. No: {{ $business->praSetting->pra_registration_no }}
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
                @continue($field !== 'document_title' || !$pc->isVisible('header', $field))
                @php $style = $pc->fieldStyle($field); @endphp
                <div
                    style="font-size:{{ $style['font_size'] ?? 14 }}px; font-weight:{{ $style['font_weight'] ?? 'bold' }}; color:{{ $style['color'] ?? '#1a1a1a' }};">
                    {{ $title }}
                </div>
            @endforeach

            {{-- A real nested table so label/value columns line up regardless of
                 label length - dompdf doesn't get the browser version's CSS
                 display:table treatment, so this uses actual <table> markup. --}}
            <table style="margin-left:auto; border-collapse:collapse;">
                @foreach ($right_fields as $field)
                    @php $style = $pc->fieldStyle($field); @endphp
                    @if ($pc->isVisible('header', $field))
                        @switch($field)
                            @case('document_no')
                                <tr>
                                    <td style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#1a1a1a' }}; white-space:nowrap; text-align:right; padding-right:6px;"><strong>Document No:</strong></td>
                                    <td style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#1a1a1a' }}; white-space:nowrap; text-align:right;">{{ $doc_no ?? 'N/A' }}</td>
                                </tr>
                            @break

                            @case('date')
                                <tr>
                                    <td style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#1a1a1a' }}; white-space:nowrap; text-align:right; padding-right:6px;"><strong>Date:</strong></td>
                                    <td style="font-size:{{ $style['font_size'] ?? 10 }}px; color:{{ $style['color'] ?? '#1a1a1a' }}; white-space:nowrap; text-align:right;">{{ $doc_date ?? 'N/A' }}</td>
                                </tr>
                            @break
                        @endswitch
                    @endif
                @endforeach

                @foreach ($reference ?? [] as $label => $value)
                    <tr>
                        <td style="font-size:10px; white-space:nowrap; text-align:right; padding-right:6px;"><strong>{{ $label }}:</strong></td>
                        <td style="font-size:10px; white-space:nowrap; text-align:right;">{{ $value ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>
