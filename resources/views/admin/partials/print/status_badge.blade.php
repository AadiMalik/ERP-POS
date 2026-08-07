{{--
    Shared print status stamp partial.
    Expects: $status (raw status value string), $posted (bool|null - only passed for accounting documents)
--}}
@php
    $status_map = [
        'pending' => ['label' => 'Pending', 'color' => 'warning'],
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
@endphp

<div class="badge-stamp-wrap">
    <div class="badge-stamp badge-stamp-{{ $status_info['color'] }}">{{ $status_info['label'] }}</div>
    @if (!is_null($posted ?? null))
        <div class="badge-stamp badge-stamp-ribbon {{ $posted ? 'badge-stamp-success' : 'badge-stamp-danger' }}">
            {{ $posted ? 'Posted to Ledger' : 'Not Yet Posted' }}
        </div>
    @endif
</div>
