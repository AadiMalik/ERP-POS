@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">My Attendance</h4>
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Date</th><th>Check In</th><th>Check Out</th><th>Working Hours</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($attendances as $item)
                    <tr>
                        <td>{{ $item->date }}</td>
                        <td>{{ $item->check_in_time ? date('h:i A', strtotime($item->check_in_time)) : '-' }}</td>
                        <td>{{ $item->check_out_time ? date('h:i A', strtotime($item->check_out_time)) : '-' }}</td>
                        <td>{{ $item->working_hours }}</td>
                        <td><span class="badge bg-label-secondary">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted">No attendance records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $attendances->links() }}
        </div>
    </div>
</div>
@endsection
