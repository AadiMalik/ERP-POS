@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_ess.my_advance_requests') }}</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div></div>
            <a href="{{ url('admin/ess/advance/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i> {{ __('hrm_ess.request_advance') }}
            </a>
        </div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>{{ __('common.amount') }}</th><th>{{ __('hrm_ess.installments') }}</th><th>{{ __('hrm_ess.remaining') }}</th><th>{{ __('common.status') }}</th></tr></thead>
                <tbody>
                    @forelse ($advances as $advance)
                    <tr>
                        <td>{{ number_format($advance->amount, 2) }}</td>
                        <td>{{ $advance->installments_count }}</td>
                        <td>{{ number_format($advance->remaining_balance, 2) }}</td>
                        <td>
                            @php
                                $map = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'repaying' => 'info', 'completed' => 'secondary'];
                                $statusLabel = match ($advance->status) {
                                    'pending' => __('hrm_ess.status_pending'),
                                    'approved' => __('hrm_ess.status_approved'),
                                    'rejected' => __('hrm_ess.status_rejected'),
                                    'repaying' => __('hrm_ess.status_repaying'),
                                    'completed' => __('hrm_ess.status_completed'),
                                    default => ucfirst($advance->status),
                                };
                            @endphp
                            <span class="badge bg-label-{{ $map[$advance->status] ?? 'secondary' }}">{{ $statusLabel }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted">{{ __('hrm_ess.no_advance_requests') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@section('js')
@if (session('success'))
<script>
    successMessage("{{ session('success') }}");
</script>
@endif
@endsection
