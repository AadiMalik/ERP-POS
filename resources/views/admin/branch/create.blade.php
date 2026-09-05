@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('branches.singular') }}</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($branch) ? __('branches.update_heading') : __('branches.new_heading') }}</h5>
        </div>

        <form action="{{ url('admin/branch') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">

                <input type="hidden" name="branch_id" value="{{ isset($branch) ? $branch->branch_id : '' }}">

                <div class="row g-4">
                    <!-- Left Column - Form Fields -->
                    <div class="col-md-12">

                        <!-- Basic Information Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">{{ __('common.basic_information') }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    @if (!empty($business))
                                    <div class="col-md-6">
                                        <label class="fw-semibold">
                                            Business <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" name="business_id" id="business_id" required>
                                            <option value="">{{ __('common.select_business') }}</option>
                                            @foreach ($business as $item)
                                            <option value="{{ $item->business_id }}"
                                                {{ old('business_id', $branch->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                                {{ $item->code }} {{ $item->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('branches.branch_name') }} <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name"
                                            value="{{ $branch->name ?? '' }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('branches.branch_code') }}</label>
                                        <input type="text" class="form-control" name="code"
                                            value="{{ $branch->code ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('branches.branch_email') }} <span
                                                class="text-danger">*</span></label>
                                        <input type="email" class="form-control" name="email"
                                            value="{{ $branch->email ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('branches.branch_phone') }} <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="phone"
                                            value="{{ $branch->phone ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address Information Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">{{ __('common.address_information') }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="fw-semibold">{{ __('common.city') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="city"
                                            value="{{ $branch->city ?? '' }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">{{ __('common.state') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" required name="state"
                                            value="{{ $branch->state ?? '' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">{{ __('common.country') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="country"
                                            value="{{ $branch->country ?? '' }}" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="fw-semibold">{{ __('common.address') }} <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="address" rows="2">{{ $branch->address ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- POS Register Mode Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">{{ __('branches.pos_register_hours') }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('branches.open_time') }}</label>
                                        <input type="time" class="form-control" name="open_time"
                                            value="{{ $branch->open_time ?? '' }}">
                                        <small class="text-muted">{{ __('branches.open_time_hint') }}</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('branches.close_time') }}</label>
                                        <input type="time" class="form-control" name="close_time"
                                            value="{{ $branch->close_time ?? '' }}">
                                        <small class="text-muted">{{ __('branches.open_time_hint') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Logo & Description Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">{{ __('branches.branch_logo') }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="fw-semibold">
                                            Logo
                                            @if (!isset($branch))
                                            <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                        <div class="border rounded-3 p-3 text-center bg-light">
                                            <img id="logoPreview"
                                                src="{{ isset($branch) && $branch->logo ? asset('public/uploads/branch/' . $branch->logo) : asset('public/assets/img/no-image.png') }}"
                                                class="img-fluid rounded-3 mb-2"
                                                style="max-height: 120px; object-fit: contain;">
                                            @if (isset($branch) && $branch->logo)
                                            <div class="mb-2">
                                                <small class="text-muted">{{ __('common.previous_logo') }}</small>
                                            </div>
                                            @endif
                                            <input type="file" id="logoInput" class="form-control" name="logo"
                                                accept="image/*" {{ !isset($branch) ? 'required' : '' }}>
                                            <small class="text-muted d-block mt-2">
                                                <i class="fa fa-info-circle"></i> {{ __('common.jpg_png_supported') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <!-- Form Actions -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('branches.save_branch') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

@section('js')
@php
    $__i18nBranches = [
        'please_select_valid_image' => __('branches.please_select_valid_image'),
    ];
@endphp
<script>window.i18n_branches = @json($__i18nBranches);</script>
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
        const logoInput = document.getElementById('logoInput');
        const logoPreview = document.getElementById('logoPreview');

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
                    alert(window.i18n_branches?.please_select_valid_image || 'Please select a valid image file (JPG, PNG)');
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