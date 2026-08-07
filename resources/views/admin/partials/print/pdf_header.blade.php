{{--
    dompdf-safe letterhead partial (table-based layout, no flexbox).
    Expects: $business, $branch (nullable), $title, $doc_no, $doc_date, $reference (assoc array, optional)
--}}
<table style="width:100%; border-bottom:2px solid #333; margin-bottom:12px;">
    <tr>
        <td style="width:60%; vertical-align:top;">
            <div style="font-size:16px; font-weight:bold;">{{ $business->name ?? 'N/A' }}</div>
            <div style="font-size:10px; color:#444;">
                {{ collect([$business->address ?? null, $business->city ?? null, $business->state ?? null, $business->country ?? null])->filter()->implode(', ') }}
            </div>
            <div style="font-size:10px; color:#444;">
                @if (!empty($business->phone))
                    Tel: {{ $business->phone }}
                @endif
                @if (!empty($business->email))
                    &nbsp;&nbsp;Email: {{ $business->email }}
                @endif
            </div>
            @if (!empty($branch))
                <div style="font-size:10px; color:#444;">
                    <strong>Branch:</strong> {{ $branch->name }}
                </div>
            @endif
        </td>
        <td style="width:40%; vertical-align:top; text-align:right;">
            <div style="font-size:14px; font-weight:bold;">{{ $title }}</div>
            <div style="font-size:10px;"><strong>Document No:</strong> {{ $doc_no ?? 'N/A' }}</div>
            <div style="font-size:10px;"><strong>Date:</strong> {{ $doc_date ?? 'N/A' }}</div>
            @foreach ($reference ?? [] as $label => $value)
                <div style="font-size:10px;"><strong>{{ $label }}:</strong> {{ $value ?? 'N/A' }}</div>
            @endforeach
        </td>
    </tr>
</table>
