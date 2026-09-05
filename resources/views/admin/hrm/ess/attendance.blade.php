@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_ess.my_attendance') }}</h4>
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead><tr><th>{{ __('common.date') }}</th><th>{{ __('hrm_ess.check_in') }}</th><th>{{ __('hrm_ess.check_out') }}</th><th>{{ __('hrm_ess.working_hours') }}</th><th>{{ __('common.status') }}</th></tr></thead>
                <tbody>
                    @forelse ($attendances as $item)
                    @php
                        $attStatus = strtolower(str_replace(' ', '_', $item->status));
                        $attLabel = match ($attStatus) {
                            'present' => __('hrm_ess.present'),
                            'absent' => __('hrm_ess.absent'),
                            'late' => __('hrm_ess.late'),
                            'half_day' => __('hrm_ess.half_day'),
                            'on_leave' => __('hrm_ess.on_leave'),
                            'on_holiday' => __('hrm_ess.on_holiday'),
                            default => ucfirst(str_replace('_', ' ', $item->status)),
                        };
                    @endphp
                    <tr>
                        <td>{{ $item->date }}</td>
                        <td>{{ $item->check_in_time ? date('h:i A', strtotime($item->check_in_time)) : '-' }}</td>
                        <td>{{ $item->check_out_time ? date('h:i A', strtotime($item->check_out_time)) : '-' }}</td>
                        <td>{{ $item->working_hours }}</td>
                        <td><span class="badge bg-label-secondary">{{ $attLabel }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted">{{ __('hrm_ess.no_attendance_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $attendances->links() }}
        </div>
    </div>
</div>
@endsection
