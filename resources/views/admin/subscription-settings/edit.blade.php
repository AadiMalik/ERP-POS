@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('subscription_settings.title') }}</h4>
        <div class="card">
            <div class="card-body">
                <form action="{{ route('subscription-settings.update') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="fw-semibold">Default {{ __('subscriptions.grace_period') }} (days)</label>
                            <input type="number" min="0" class="form-control" name="default_grace_period_days" value="{{ $setting->default_grace_period_days }}">
                            <small class="text-muted">How many days a business keeps access after expiry before being restricted.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Reminder Thresholds (days before expiry, comma separated)</label>
                            <input type="text" class="form-control" name="reminder_thresholds_days" value="{{ implode(', ', $setting->reminder_thresholds_days ?? [30, 15, 7, 3, 1]) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Invoice Number Prefix</label>
                            <input type="text" class="form-control" name="invoice_prefix" value="{{ $setting->invoice_prefix }}">
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Subscription Expiry Alert (days before expiry)</label>
                            <input type="number" min="0" class="form-control" name="expiry_alert_days_before" value="{{ $setting->expiry_alert_days_before ?? 5 }}">
                            <small class="text-muted">In-app bell alert sent to the Business Admin and Super Admin this many days before a subscription expires.</small>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="restrict_access_in_grace_period" id="restrictAccess" value="1" {{ $setting->restrict_access_in_grace_period ? 'checked' : '' }}>
                                <label class="form-check-label" for="restrictAccess">Restrict write access during grace period</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4 pt-3 border-top">
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
@endsection
