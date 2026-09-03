{{--
    Shared print status stamp partial.
    Expects: $status (raw status value string), $posted (bool|null - only passed for accounting documents)
    Optional: $print_config (App\Support\Print\PrintConfig)
--}}
@php
    $status_map = [
        'pending' => ['label' => 'Pending', 'color' => 'warning'],
        'draft' => ['label' => 'Draft', 'color' => 'secondary'],
        'in_transit' => ['label' => 'In Transit', 'color' => 'warning'],
        'approved' => ['label' => 'Approved', 'color' => 'success'],
        'posted' => ['label' => 'Posted', 'color' => 'success'],
        'completed' => ['label' => 'Completed', 'color' => 'success'],
        'cancelled' => ['label' => 'Cancelled', 'color' => 'danger'],
        'rejected' => ['label' => 'Rejected', 'color' => 'danger'],
        'sent' => ['label' => 'Sent', 'color' => 'info'],
        'received' => ['label' => 'Received', 'color' => 'info'],
        'selected' => ['label' => 'Selected', 'color' => 'info'],
        'quotation sent' => ['label' => 'Quotation Sent', 'color' => 'info'],
        'quotation received' => ['label' => 'Quotation Received', 'color' => 'info'],
        'converted' => ['label' => 'Converted', 'color' => 'secondary'],
    ];

    $status_key = strtolower($status ?? '');
    $status_info = $status_map[$status_key] ?? ['label' => ucfirst($status ?? 'N/A'), 'color' => 'secondary'];

    $pc = $print_config ?? new \App\Support\Print\PrintConfig(config('print_defaults'));
    $show_status = $pc->isVisible('header', 'status_badge');
    $show_posted = $pc->isVisible('header', 'posting_status_badge');
    $status_align = $pc->fieldStyle('status_badge')['align'] ?? 'right';
@endphp

@if ($show_status || (!is_null($posted ?? null) && $show_posted))
    <div class="badge-stamp-wrap"
        style="justify-content: {{ $status_align === 'left' ? 'flex-start' : ($status_align === 'center' ? 'center' : 'flex-end') }};">
        @if ($show_status)
            <div class="badge-stamp badge-stamp-{{ $status_info['color'] }}">{{ $status_info['label'] }}</div>
        @endif
        @if (!is_null($posted ?? null) && $show_posted)
            <div class="badge-stamp badge-stamp-ribbon {{ $posted ? 'badge-stamp-success' : 'badge-stamp-danger' }}">
                {{ $posted ? 'Posted to Ledger' : 'Not Yet Posted' }}
            </div>
        @endif
    </div>
@endif
