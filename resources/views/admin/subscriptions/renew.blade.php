@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Renew Subscription - {{ $business->name }}</h4>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('subscriptions.renew', $business->business_id) }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="fw-semibold">Package <span class="text-danger">*</span></label>
                            <select class="form-select" name="package_id" id="packageSelect" required>
                                @foreach ($packages as $item)
                                    <option value="{{ $item->package_id }}" data-price="{{ $item->price }}" data-duration_type="{{ $item->duration_type }}"
                                        {{ ($subscription->package_id ?? '') == $item->package_id ? 'selected' : '' }}>
                                        {{ $item->name }} ({{ currency($item->price) }} / {{ $item->duration_type }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Billing Cycle <span class="text-danger">*</span></label>
                            <select class="form-select" name="billing_cycle" id="billingCycleSelect" required>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="fw-semibold">Discount</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="discount" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold">Discount Type</label>
                            <select class="form-select" name="discount_type">
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold">Tax (%)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="tax" value="0">
                        </div>

                        <div class="col-12"><hr></div>

                        <div class="col-md-4">
                            <label class="fw-semibold">Payment Method</label>
                            <select class="form-select" name="payment_method">
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold">Payment Reference</label>
                            <input type="text" class="form-control" name="payment_reference">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold">Payment Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="payment_amount" placeholder="Defaults to invoice total">
                        </div>

                        <div class="col-md-6">
                            <label class="fw-semibold">Due Date</label>
                            <input type="date" class="form-control" name="due_date">
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Notes</label>
                            <input type="text" class="form-control" name="notes">
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mark_paid" id="markPaid" value="1" checked>
                                <label class="form-check-label" for="markPaid">Mark as paid immediately</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="{{ route('subscriptions.show', $business->business_id) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button class="btn btn-primary px-4">Confirm Renewal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $('#packageSelect').on('change', function() {
            var type = $(this).find(':selected').data('duration_type');
            if (type) {
                $('#billingCycleSelect').val(type);
            }
        }).trigger('change');
    </script>
@endsection
