@extends('layouts.app')

@section('css')
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    <h4 class="fw-bold py-3 mb-4">
        Packages
    </h4>

    <div class="card">

        <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">
                {{ isset($package) ? 'Update' : 'New' }} Package
            </h5>
        </div>


        <form action="{{ url('admin/packages') }}" method="POST">

            @csrf
            <div class="card-body">

                <input type="hidden" name="package_id" value="{{ $package->package_id ?? '' }}">

                <div class="row">

                    {{-- Name --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Name
                        </label>

                        <input type="text" class="form-control" name="name" required
                            value="{{ $package->name ?? '' }}">
                    </div>

                    {{-- Price --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Price
                        </label>

                        <input type="number" step="0.01" class="form-control" name="price" required
                            value="{{ $package->price ?? '' }}">
                    </div>

                    {{-- Order --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Order
                        </label>

                        <input type="number" class="form-control" name="order" value="{{ $package->order ?? '' }}">
                    </div>

                    {{-- Duration Type --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Duration Type
                        </label>

                        <select class="form-select" name="duration_type">

                            <option value="monthly"
                                {{ isset($package) && $package->duration_type == 'monthly' ? 'selected' : '' }}>
                                Monthly
                            </option>

                            <option value="yearly"
                                {{ isset($package) && $package->duration_type == 'yearly' ? 'selected' : '' }}>
                                Yearly
                            </option>

                        </select>

                    </div>

                    {{-- Duration --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Duration Days
                        </label>

                        <input type="number" class="form-control" name="duration_days"
                            value="{{ $package->duration_days ?? '' }}">
                    </div>

                    {{-- Status --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select class="form-select" name="status">

                            <option value="1" {{ isset($package) && $package->status == 1 ? 'selected' : '' }}>
                                Active
                            </option>

                            <option value="0" {{ isset($package) && $package->status == 0 ? 'selected' : '' }}>
                                Inactive
                            </option>

                        </select>

                    </div>

                    {{-- Limits --}}
                    @php
                    $fields = [
                    'max_branches',
                    'max_users',
                    'max_customers',
                    'max_warehouses',
                    'max_categories',
                    'max_products',
                    'max_suppliers',
                    'max_purchase_orders',
                    'max_purchases',
                    'max_sales',
                    'max_transfers',
                    'max_expenses',
                    'max_vouchers',
                    ];
                    @endphp

                    @foreach ($fields as $field)
                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            {{ ucwords(str_replace('_', ' ', $field)) }}
                        </label>

                        <input type="number" class="form-control" name="{{ $field }}"
                            value="{{ $package->$field ?? '' }}">

                    </div>
                    @endforeach


                    {{-- Modules --}}

                    @php
                    $modules = [
                    'is_pos_enabled' => 'POS',
                    'is_inventory_enabled' => 'Inventory',
                    'is_accounting_enabled' => 'Accounting',
                    'is_hrm_enabled' => 'HRM',
                    'is_payroll_enabled' => 'Payroll',
                    ];
                    @endphp

                    <div class="col-md-12">

                        <h6>
                            Enabled Modules
                        </h6>

                    </div>

                    @foreach ($modules as $key => $label)
                    <div class="col-md-3 mb-3">

                        <div class="form-check">

                            <input type="checkbox" class="form-check-input" name="{{ $key }}"
                                id="{{ $key }}" {{ isset($package) && $package->$key ? 'checked' : '' }}>

                            <label class="form-check-label" for="{{ $key }}">

                                {{ $label }}

                            </label>

                        </div>

                    </div>
                    @endforeach


                    {{-- Description --}}
                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea class="form-control" rows="5" name="description">{{ $package->description ?? '' }}</textarea>

                    </div>

                </div>
            </div>

            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Save</button>
                </div>
            </div>
        </form>

    </div>

</div>
@endsection


@section('js')
@if (session('error'))
<script>
    errorMessage(
        "{{ session('error') }}"
    );
</script>
@endif
@endsection