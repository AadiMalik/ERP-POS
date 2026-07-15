@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Supplier</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($supplier) ? 'Update' : 'New' }} Supplier</h5>
        </div>

        <form action="{{ url('admin/supplier') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">

                <input type="hidden" name="supplier_id" value="{{ isset($supplier) ? $supplier->supplier_id : '' }}">

                <div class="row g-4">
                    <!-- Left Column - Form Fields -->
                    <div class="col-md-12">

                        <!-- Basic Information Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Basic Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    @if (!empty($business))
                                    @if (RoleNames::SUPERADMIN == getRoleName())
                                    <div class="col-md-6">
                                        <label class="fw-semibold">Business <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" name="business_id" id="business_id"
                                            required>
                                            <option value="">-- Select Business --</option>
                                            @foreach ($business as $item)
                                            <option value="{{ $item->business_id }}"
                                                {{ old('business_id', $supplier->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                                {{ $item->code }} - {{ $item->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif
                                    @endif

                                    <div class="col-md-6">
                                        <label class="fw-semibold">
                                            Code <small>(if blank, will be auto generated)</small>
                                        </label>

                                        <div class="input-group">
                                            <span class="input-group-text">{{ $prefix }}</span>

                                            <input type="text"
                                                class="form-control"
                                                name="code"
                                                value="{{ old('code', isset($supplier) ? str_replace($prefix, '', $supplier->code) : '') }}" {{isset($supplier) ? 'readonly' : ''}}>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">Name <span class="text-danger">**</span>
                                        </label>
                                        <input type="text" class="form-control" name="name"
                                            value="{{ old('name', $supplier->name ?? '') }}" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">Company Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="company_name"
                                            value="{{ old('company_name', $supplier->company_name ?? '') }}" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">Contact Person</label>
                                        <input type="text" class="form-control" name="contact_person"
                                            value="{{ old('contact_person', $supplier->contact_person ?? '') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">Email</label>
                                        <input type="email" class="form-control" name="email"
                                            value="{{ old('email', $supplier->email ?? '') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">Phone</label>
                                        <input type="text" class="form-control" name="phone"
                                            value="{{ old('phone', $supplier->phone ?? '') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">Website</label>
                                        <input type="text" class="form-control" name="website"
                                            value="{{ old('website', $supplier->website ?? '') }}">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="fw-semibold">NTN</label>
                                        <input type="text" class="form-control" name="ntn"
                                            value="{{ old('ntn', $supplier->ntn ?? '') }}">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="fw-semibold">STRN</label>
                                        <input type="text" class="form-control" name="strn"
                                            value="{{ old('strn', $supplier->strn ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address Information Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Address Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="fw-semibold">Address</label>
                                        <textarea class="form-control" rows="2" name="address">{{ old('address', $supplier->address ?? '') }}</textarea>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="fw-semibold">City</label>
                                        <input type="text" class="form-control" name="city"
                                            value="{{ old('city', $supplier->city ?? '') }}">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="fw-semibold">State</label>
                                        <input type="text" class="form-control" name="state"
                                            value="{{ old('state', $supplier->state ?? '') }}">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="fw-semibold">Country</label>
                                        <input type="text" class="form-control" name="country"
                                            value="{{ old('country', $supplier->country ?? '') }}">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="fw-semibold">Zip Code</label>
                                        <input type="text" class="form-control" name="zip_code"
                                            value="{{ old('zip_code', $supplier->zip_code ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Credit Information</h6>
                            </div>

                            <div class="card-body">
                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <label class="fw-semibold">Credit Limit</label>
                                        <input type="number" step="0.01" class="form-control"
                                            name="credit_limit"
                                            value="{{ old('credit_limit', $supplier->credit_limit ?? 0) }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">Credit Days</label>
                                        <input type="number" class="form-control" name="credit_days"
                                            value="{{ old('credit_days', $supplier->credit_days ?? 0) }}">
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Logo & Description Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Image</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="fw-semibold">
                                            Image
                                            @if (!isset($supplier))
                                            <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                        <div class="border rounded-3 p-3 text-center bg-light">
                                            <img id="logoPreview"
                                                src="{{ isset($supplier) && $supplier->image ? asset('public/uploads/supplier/' . $supplier->image) : asset('public/assets/img/no-image.png') }}"
                                                class="img-fluid rounded-3 mb-2"
                                                style="max-height: 120px; object-fit: contain;">
                                            @if (isset($supplier) && $supplier->image)
                                            <div class="mb-2">
                                                <small class="text-muted">Previous Image</small>
                                            </div>
                                            @endif
                                            <input type="file" id="logoInput" class="form-control" name="image"
                                                accept="image/*">
                                            <small class="text-muted d-block mt-2">
                                                <i class="fa fa-info-circle"></i> JPG, PNG supported
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Description</h6>
                            </div>
                            <div class="card-body">
                                <textarea class="form-control" rows="4" name="description">{{ old('description', $supplier->description ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <!-- Form Actions -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Save Supplier</button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

@section('js')
@if ($errors->any())
<script>
    errorMessage("{{ $errors->first() }}");
</script>
@endif
@if (session('error'))
<script>
    errorMessage(
        "{{ session('error') }}"
    );
</script>
@endif
<script>
    $(document).ready(function() {
        $('#business_id').select2();
    });
    (function() {

        // Logo preview handler
        if (logoInput && logoPreview) {
            logoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        logoPreview.src = event.target.result;
                    };
                    reader.readAsDataURL(file);
                } else if (file) {
                    alert('Please select a valid image file (JPG, PNG)');
                    logoInput.value = '';
                }
            });
        }
    })();
</script>
@endsection

@section('css')
<style>
    .sticky-top {
        position: sticky;
        top: 20px;
        z-index: 1;
    }

    .package-detail-item {
        transition: all 0.2s ease;
    }

    .package-detail-item:hover {
        background-color: #f8f9fa;
        margin-left: 5px;
        padding-left: 5px;
        border-radius: 8px;
    }

    #packageInfoBox {
        min-height: 400px;
    }

    #nextExpiryBox {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
    }

    .card-header.bg-primary {
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
    }
</style>
@endsection