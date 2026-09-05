@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_ess.my_resignation_requests') }}</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div></div>
            @if ($exits->whereIn('status', ['pending', 'approved'])->count() == 0)
            <a href="{{ url('admin/ess/exit/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i> {{ __('hrm_ess.submit_resignation') }}
            </a>
            @endif
        </div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>{{ __('hrm_ess.request_date') }}</th><th>{{ __('hrm_ess.last_working_date') }}</th><th>{{ __('common.status') }}</th></tr></thead>
                <tbody>
                    @forelse ($exits as $exit)
                    <tr>
                        <td>{{ $exit->request_date }}</td>
                        <td>{{ $exit->last_working_date ?? '-' }}</td>
                        <td>
                            @php
                                $map = ['pending' => 'warning', 'approved' => 'info', 'rejected' => 'danger', 'finalized' => 'success'];
                                $statusLabel = match ($exit->status) {
                                    'pending' => __('hrm_ess.status_pending'),
                                    'approved' => __('hrm_ess.status_approved'),
                                    'rejected' => __('hrm_ess.status_rejected'),
                                    'finalized' => __('hrm_ess.status_finalized'),
                                    default => ucfirst($exit->status),
                                };
                            @endphp
                            <span class="badge bg-label-{{ $map[$exit->status] ?? 'secondary' }}">{{ $statusLabel }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-muted">{{ __('hrm_ess.no_resignation_requests') }}</td></tr>
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
