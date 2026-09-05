@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Backup Settings</h4>
        <div class="card">
            <div class="card-body">
                <form action="{{ route('backup-settings.update') }}" method="POST">
                    @csrf
                    <h6 class="fw-semibold">Automatic Schedule</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_scheduled_enabled" id="isScheduledEnabled" value="1" {{ $setting->is_scheduled_enabled ? 'checked' : '' }}>
                                <label class="form-check-label" for="isScheduledEnabled">Enable automatic backups</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold">Frequency</label>
                            <select name="frequency" id="frequency" class="form-select">
                                <option value="daily" {{ $setting->frequency == 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ $setting->frequency == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="monthly" {{ $setting->frequency == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold">Run Time (24h)</label>
                            <input type="time" name="run_time" class="form-control" value="{{ $setting->run_time }}">
                        </div>
                        <div class="col-md-4" id="dayOfWeekWrap" style="{{ $setting->frequency == 'weekly' ? '' : 'display:none' }}">
                            <label class="fw-semibold">Day of Week</label>
                            <select name="day_of_week" class="form-select">
                                @foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $i => $day)
                                    <option value="{{ $i }}" {{ $setting->day_of_week == $i ? 'selected' : '' }}>{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4" id="dayOfMonthWrap" style="{{ $setting->frequency == 'monthly' ? '' : 'display:none' }}">
                            <label class="fw-semibold">Day of Month (1-28)</label>
                            <input type="number" min="1" max="28" name="day_of_month" class="form-control" value="{{ $setting->day_of_month ?? 1 }}">
                        </div>
                    </div>

                    <h6 class="fw-semibold">Retention &amp; Storage Limits</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="fw-semibold">Keep backups for (days)</label>
                            <input type="number" min="1" class="form-control" name="retention_days" value="{{ $setting->retention_days }}">
                            <small class="text-muted">Older backups are removed automatically. The most recent successful backup is never deleted.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold">Maximum total backup storage (MB)</label>
                            <input type="number" min="100" class="form-control" name="max_storage_mb" value="{{ $setting->max_storage_mb }}" placeholder="No limit">
                        </div>
                    </div>

                    <h6 class="fw-semibold">Storage Destination</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            @foreach ($availableDisks as $key => $label)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="disks[]" id="disk_{{ $key }}" value="{{ $key }}" {{ in_array($key, $setting->disks ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="disk_{{ $key }}">{{ $label }}</label>
                                </div>
                            @endforeach
                            @if (count($availableDisks) === 1)
                                <small class="text-muted">Configure AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY / AWS_BUCKET in the environment to enable cloud storage as an additional destination.</small>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                        <a href="{{ route('backups.index') }}" class="btn btn-outline-secondary me-2">Back</a>
                        <button class="btn btn-primary px-4">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @if (session('success'))
        <script>successMessage("{{ session('success') }}");</script>
    @endif
    <script>
        $('#frequency').on('change', function () {
            $('#dayOfWeekWrap').toggle($(this).val() === 'weekly');
            $('#dayOfMonthWrap').toggle($(this).val() === 'monthly');
        });
    </script>
@endsection
