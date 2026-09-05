@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Payment Transaction</h4>
    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Order:</strong> {{ $transaction->order?->daily_order_id ?? $transaction->order_id }}</div>
                <div class="col-md-4"><strong>Gateway:</strong> {{ $transaction->paymentGateway?->display_name ?? $transaction->provider_code }}</div>
                <div class="col-md-4"><strong>Environment:</strong> {{ ucfirst($transaction->environment) }}</div>
                <div class="col-md-4"><strong>Platform:</strong> {{ ucfirst($transaction->client_platform) }}</div>
                <div class="col-md-4"><strong>Amount:</strong> {{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}</div>
                <div class="col-md-4"><strong>Refunded:</strong> {{ number_format($transaction->refunded_amount, 2) }}</div>
                <div class="col-md-4"><strong>Status:</strong> {{ ucfirst(str_replace('_',' ',$transaction->status)) }}</div>
                <div class="col-md-4"><strong>Internal Reference:</strong> {{ $transaction->internal_reference }}</div>
                <div class="col-md-4"><strong>Gateway Transaction ID:</strong> {{ $transaction->gateway_transaction_id ?? '-' }}</div>
                <div class="col-md-4"><strong>Verification Method:</strong> {{ $transaction->verification_method ?? '-' }}</div>
                <div class="col-md-4"><strong>Verified At:</strong> {{ $transaction->verified_at ?? '-' }}</div>
                @if ($transaction->failure_reason)
                <div class="col-md-12"><strong>Failure Reason:</strong> {{ $transaction->failure_reason }} ({{ $transaction->failure_code }})</div>
                @endif
            </div>
        </div>
        <div class="card-footer">
            <a href="{{ url('admin/payment-transaction') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>
</div>
@endsection
