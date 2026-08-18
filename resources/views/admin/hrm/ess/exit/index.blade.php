@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">My Resignation Requests</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div></div>
            @if ($exits->whereIn('status', ['pending', 'approved'])->count() == 0)
            <a href="{{ url('admin/ess/exit/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i> Submit Resignation
            </a>
            @endif
        </div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Request Date</th><th>Last Working Date</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($exits as $exit)
                    <tr>
                        <td>{{ $exit->request_date }}</td>
                        <td>{{ $exit->last_working_date ?? '-' }}</td>
                        <td>
                            @php
                                $map = ['pending' => 'warning', 'approved' => 'info', 'rejected' => 'danger', 'finalized' => 'success'];
                            @endphp
                            <span class="badge bg-label-{{ $map[$exit->status] ?? 'secondary' }}">{{ ucfirst($exit->status) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-muted">No resignation requests submitted.</td></tr>
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
