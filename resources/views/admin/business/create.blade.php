@extends('layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Business</h4>

        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($business) ? 'Update' : 'New' }} Business</h5>
            </div>

            <div class="card-body">
                <form action="{{ url('admin/business') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <input type="hidden" name="business_id" value="{{ isset($business) ? $business->business_id : '' }}">

                    <div class="row g-4">
                        <!-- Left Column - Form Fields -->
        <div class="col-md-7">
                            @if (!isset($business))
                                <!-- Subscription Package Section (new business only - existing
                                     businesses change plans exclusively through Renew Subscription) -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Subscription Package</h6>
                                    </div>
                                    <div class="card-body">
                                        <label class="fw-semibold mb-2">
                                            Select Package <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" name="package_id" id="packageSelect" required>
                                            <option value="">-- Select Package --</option>
                                            @foreach ($packages as $item)
                                                <option value="{{ $item->package_id }}" data-name="{{ $item->name }}"
                                                    data-price="{{ $item->price }}"
                                                    data-duration_type="{{ $item->duration_type }}"
                                                    data-duration_days="{{ $item->duration_days }}"
                                                    data-description="{{ $item->description }}"
                                                    {{ old('package_id') == $item->package_id ? 'selected' : '' }}>
                                                    {{ $item->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @else
                                <div class="card mb-4">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Subscription Package</h6>
                                        <a href="{{ route('subscriptions.show', $business->business_id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-refresh"></i> Manage Subscription
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-0">
                                            Current plan: <strong>{{ $business->package->name ?? '-' }}</strong>.
                                            Plan and billing changes are made from the Subscription page, not here.
                                        </p>
                                    </div>
                                </div>
                            @endif

                            <!-- Basic Information Section -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="fw-semibold">Business Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="name"
                                                value="{{ $business->name ?? '' }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="fw-semibold">Business Code</label>
                                            <input type="text" class="form-control" name="code"
                                                value="{{ $business->code ?? '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="fw-semibold">Business Email <span
                                                    class="text-danger">*</span></label>
                                            <input type="email" class="form-control" name="email"
                                                value="{{ $business->email ?? '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="fw-semibold">Business Phone <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="phone"
                                                value="{{ $business->phone ?? '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="fw-semibold">Owner Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="owner_name" required
                                                value="{{ $business->owner_name ?? '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="fw-semibold">Owner Email <span
                                                    class="text-danger">*</span></label>
                                            <input type="email" class="form-control" name="owner_email" required
                                                value="{{ $business->owner_email ?? '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="fw-semibold">Owner Phone <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="owner_phone" required
                                                value="{{ $business->owner_phone ?? '' }}">
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
                                        <div class="col-md-4">
                                            <label class="fw-semibold">City <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="city"
                                                value="{{ $business->city ?? '' }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="fw-semibold">State <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" required name="state"
                                                value="{{ $business->state ?? '' }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="fw-semibold">Country <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="country"
                                                value="{{ $business->country ?? '' }}" required>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="fw-semibold">Address <span class="text-danger">*</span></label>
                                            <textarea class="form-control" name="address" rows="2">{{ $business->address ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Logo & Description Section -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Media & Description</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="fw-semibold">
                                                Logo
                                                @if (!isset($business))
                                                    <span class="text-danger">*</span>
                                                @endif
                                            </label>
                                            <div class="border rounded-3 p-3 text-center bg-light">
                                                <img id="logoPreview"
                                                    src="{{ isset($business) && $business->logo ? asset('public/uploads/business/' . $business->logo) : asset('public/assets/img/no-image.png') }}"
                                                    class="img-fluid rounded-3 mb-2"
                                                    style="max-height: 120px; object-fit: contain;">
                                                @if (isset($business) && $business->logo)
                                                    <div class="mb-2">
                                                        <small class="text-muted">Previous Logo</small>
                                                    </div>
                                                @endif
                                                <input type="file" id="logoInput" class="form-control" name="logo"
                                                    accept="image/*" {{ !isset($business) ? 'required' : '' }}>
                                                <small class="text-muted d-block mt-2">
                                                    <i class="fa fa-info-circle"></i> JPG, PNG supported
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="fw-semibold">Description</label>
                                            <textarea class="form-control" rows="5" name="description" placeholder="Enter business description...">{{ $business->description ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Package Info Box -->
                        <div class="col-md-5">
                            <div class="card mb-4 sticky-top" style="top: 20px;">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fa fa-info-circle me-2"></i> Package Information</h6>
                                </div>
                                <div class="card-body" id="packageInfoBox">
                                    <!-- Default message when no package selected -->
                                    <div class="text-center text-muted py-4" id="noPackageMsg">
                                        <i class="fa fa-archive fa-3x mb-3 d-block"></i>
                                        <p>Select a package to view<br>details and expiry information</p>
                                    </div>

                                    <!-- Package details will be displayed here -->
                                    <div id="packageDetails" class="d-none">
                                        <div class="package-detail-item mb-3 pb-2 border-bottom">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="fa fa-cube text-primary"></i>
                                                <strong>Package Name:</strong>
                                            </div>
                                            <span id="detailName" class="ms-4 d-block">--</span>
                                        </div>

                                        <div class="package-detail-item mb-3 pb-2 border-bottom">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="fa fa-tag text-primary"></i>
                                                <strong>Price:</strong>
                                            </div>
                                            <span id="detailPrice" class="ms-4 d-block">--</span>
                                        </div>

                                        <div class="package-detail-item mb-3 pb-2 border-bottom">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="fa fa-clock-o text-primary"></i>
                                                <strong>Duration Type:</strong>
                                            </div>
                                            <span id="detailDurationType" class="ms-4 d-block">--</span>
                                        </div>

                                        <div class="package-detail-item mb-3 pb-2 border-bottom">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="fa fa-calendar text-primary"></i>
                                                <strong>Duration Days:</strong>
                                            </div>
                                            <span id="detailDurationDays" class="ms-4 d-block">--</span>
                                        </div>

                                        <div class="package-detail-item mb-3 pb-2 border-bottom">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="fa fa-align-left text-primary"></i>
                                                <strong>Description:</strong>
                                            </div>
                                            <span id="detailDescription" class="ms-4 d-block text-muted small">--</span>
                                        </div>

                                        <!-- Next Expiry Section -->
                                        <div class="package-detail-item mt-3 pt-2">
                                            <div class="alert alert-warning mb-0" id="nextExpiryBox">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-calendar-check-o fa-lg"></i>
                                                    <div>
                                                        <strong>Next Expiry Date</strong><br>
                                                        <span id="expiryDateText" class="fw-bold">--</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
                        <button type="button" class="btn btn-outline-secondary"
                            onclick="window.history.back()">Cancel</button>
                        <button class="btn btn-primary px-4">Save Business</button>
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
            errorMessage("{{ session('error') }}");
        </script>
    @endif
    <script>
        $(document).ready(function() {
            $('#packageSelect').select2();
        });
        (function() {
            const pkgSelect = document.getElementById('packageSelect');
            const noPackageMsg = document.getElementById('noPackageMsg');
            const packageDetails = document.getElementById('packageDetails');
            const logoInput = document.getElementById('logoInput');
            const logoPreview = document.getElementById('logoPreview');

            // Detail spans
            const detailName = document.getElementById('detailName');
            const detailPrice = document.getElementById('detailPrice');
            const detailDurationType = document.getElementById('detailDurationType');
            const detailDurationDays = document.getElementById('detailDurationDays');
            const detailDescription = document.getElementById('detailDescription');
            const expiryDateText = document.getElementById('expiryDateText');

            // Function to format price
            function formatPrice(price) {
                let num = parseFloat(price);
                if (isNaN(num)) return '0.00';
                return num.toFixed(2);
            }

            // Function to format date
            function formatDate(date) {
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }

            // Function to update package details and expiry
            function updatePackageInfo() {
                const selectedOption = pkgSelect.options[pkgSelect.selectedIndex];

                if (!selectedOption || !selectedOption.value) {
                    // No package selected - show message, hide details
                    noPackageMsg.classList.remove('d-none');
                    packageDetails.classList.add('d-none');
                    return;
                }

                // Hide no package message and show details
                noPackageMsg.classList.add('d-none');
                packageDetails.classList.remove('d-none');

                // Get package data from data attributes
                const name = selectedOption.dataset.name || 'N/A';
                const price = formatPrice(selectedOption.dataset.price);
                const durationType = selectedOption.dataset.duration_type || 'N/A';
                const durationDays = selectedOption.dataset.duration_days || '0';
                const description = selectedOption.dataset.description || 'No description available';

                // Update detail fields
                detailName.textContent = name;
                detailPrice.textContent = `${price}`;
                detailDurationType.textContent = durationType.charAt(0).toUpperCase() + durationType.slice(1);
                detailDurationDays.textContent = durationDays;
                detailDescription.textContent = description;

                // Calculate and update next expiry date
                let expiryDate = new Date();

                if (durationType.toLowerCase() === 'monthly') {
                    expiryDate.setMonth(expiryDate.getMonth() + parseInt(durationDays));
                } else if (durationType.toLowerCase() === 'yearly') {
                    expiryDate.setFullYear(expiryDate.getFullYear() + parseInt(durationDays));
                } else {
                    expiryDate.setDate(expiryDate.getDate() + parseInt(durationDays));
                }

                expiryDateText.textContent = formatDate(expiryDate);
            }

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

            // Package select change event
            if (pkgSelect) {
                pkgSelect.addEventListener('change', updatePackageInfo);
                // Initial update for edit mode (if package is pre-selected)
                updatePackageInfo();
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
