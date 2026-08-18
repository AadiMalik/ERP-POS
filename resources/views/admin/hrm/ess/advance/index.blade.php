@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">My Advance Requests</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div></div>
            <a href="{{ url('admin/ess/advance/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i> Request Advance
            </a>
        </div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Amount</th><th>Installments</th><th>Remaining</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($advances as $advance)
                    <tr>
                        <td>{{ number_format($advance->amount, 2) }}</td>
                        <td>{{ $advance->installments_count }}</td>
                        <td>{{ number_format($advance->remaining_balance, 2) }}</td>
                        <td>
                            @php
                                $map = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'repaying' => 'info', 'completed' => 'secondary'];
                            @endphp
                            <span class="badge bg-label-{{ $map[$advance->status] ?? 'secondary' }}">{{ ucfirst($advance->status) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted">No advance requests yet.</td></tr>
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
